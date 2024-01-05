<?php

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\User;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repository\InventoryItemRepository;

class RestoreController extends AbstractController
{
    function __construct(
        private readonly Utils $utils,
        private readonly string $projectDir,
        private readonly EntityManagerInterface $em,
    ) {
    }
    #[Route('/restore', name: 'restore')]
    public function restore(#[CurrentUser] ?User $user, Request $request, KernelInterface $kernel)
    {
        if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
            return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
        }

        $what = $request->get('form')['import'];

        switch ($what) {
            case BackupController::INVENTORY_DATA:
                /** @var UploadedFile */
                $uploadedFile = $pathName = $request->files->get('form')['inventoryCsvFile'];

                $overwrite = array_key_exists('overwrite', $request->get('form'));

                $pathName = $uploadedFile->getPathname();
                $fileName = $uploadedFile->getFilename();

                $targetFilePath = $this->utils->pathCombine([$this->projectDir, 'uploads/csv', $fileName]);
                if (move_uploaded_file($pathName, $targetFilePath)) {
                    // Open the file
                    try {
                        if (($handle = fopen($targetFilePath, "r")) !== false) {
                            // Read and process the lines. 
                            // Skip the first line if the file includes a header
                            fgetcsv($handle, 1000, ';'); // Get (and skip) the header row

                            // Delete all user's inventory items before restoring given inventory
                            if ($overwrite) {
                                /** @var InventoryItemRepository $inventoryItemRepo */
                                $inventoryItemRepo = $this->em->getRepository(InventoryItem::class);
                                $inventoryItemRepo->deleteAllByUser($user);
                            }

                            while (($data = fgetcsv($handle)) !== false) {
                                // Assign fields
                                $data = explode(';', $data[0]);
                                $inventoryItem = InventoryItem::fromArray($data, $user);
                                $this->em->persist($inventoryItem);
                            }
                            fclose($handle);

                            $this->em->flush();
                        }

                        // Optionally, you can delete the uploaded file when done
                        // TODO: unlink not working
                        // unlink($targetFilePath);
                    } catch (Exception $e) {
                        throw new JsonResponse(['success' => true, 'message' => 'Failed parsing uploaded file, please try with a different file.']);
                    }
                } else {
                    // Handle move_uploaded_file error
                    throw new JsonResponse(['success' => true, 'message' => 'Failed handling uploaded file, please try again.']);
                }

                return new JsonResponse(['success' => true]);

            case BackupController::SETTINGS_DATA:
                $application = new Application($kernel);
                $application->setAutoExit(false);

                $backupId = $request->get('form')['backup'];

                $input = new ArrayInput([
                    'command' => 'restore_data',
                    // (optional) define the value of command arguments
                    'user' => $user->getId(),
                    'backup' => $backupId,
                ]);

                // You can use NullOutput() if you don't need the output
                $output = new BufferedOutput();
                $application->run($input, $output);

                // return the output, don't use if you used NullOutput()
                $content = $output->fetch();
                if (str_contains(strtolower($content), "error")) {
                    $this->addFlash("error", $content);
                } elseif (str_contains(strtolower($content), "success")) {
                    $this->addFlash("success", "📥 Restore completed");
                } else {
                    $this->addFlash("error", "Restore error. Please try again");
                }
                return $this->redirectToRoute("profile_settings");

            default:
                # code...
                break;
        }

        /*
        $application = new Application($this->get('kernel'));
        $command = $application->find('app:restore-data');

        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->run($input, $output);
        */

        $this->addFlash("error", "⚠️ Data type not supported");
        return $this->redirectToRoute("profile_settings");
    }
}
