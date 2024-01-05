<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class BackupController extends AbstractController
{
    final public const INVENTORY_DATA = 'inventory';
    final public const SETTINGS_DATA = 'settings';

    function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/backup', name: 'backup')]
    public function backup(#[CurrentUser] ?User $user, Request $request, KernelInterface $kernel)
    {
        if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
            return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
        }

        $what = $request->get('form')['export'];

        switch ($what) {
            case BackupController::INVENTORY_DATA:
                $response = $this->exportInventory($user);
                return $response;

            case BackupController::SETTINGS_DATA:
                $application = new Application($kernel);
                $application->setAutoExit(false);

                $input = new ArrayInput([
                    'command' => 'backup_data',
                    // (optional) define the value of command arguments
                    'user' => $user->getId(),
                    '--data' => BackupController::SETTINGS_DATA,
                ]);

                // You can use NullOutput() if you don't need the output
                $output = new BufferedOutput();
                $application->run($input, $output);

                // return the output, don't use if you used NullOutput()
                $content = $output->fetch();
                if (str_contains(strtolower($content), "error")) {
                    $this->addFlash("error", $content);
                } elseif (str_contains(strtolower($content), "success")) {
                    $this->addFlash("success", "📤 Backup completed");
                } else {
                    $this->addFlash("error", "Backup error. Please try again");
                }
                break;
        }

        return $this->redirectToRoute("profile_settings");
    }

    private function exportInventory(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        [$fileName, $filePath] = $userRepository->exportInventoryToCSV($user);

        $response = new BinaryFileResponse($filePath);

        // Set the Content-Disposition header to attachment to force download
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        // Automatically delete the file after sending it to the client
        $response->deleteFileAfterSend(true);

        //$this->addFlash("success", "⬇️ Inventory downloaded successfully!");

        return $response;
    }
}
