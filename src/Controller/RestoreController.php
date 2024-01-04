<?php

namespace App\Controller;

use App\Entity\User;
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

class RestoreController extends AbstractController
{
    #[Route('/restore', name: 'restore')]
    public function restore(#[CurrentUser] ?User $user, Request $request, KernelInterface $kernel)
    {
        if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
            return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
        }

        $what = $request->get('form')['import'];

        switch ($what) {
            case BackupController::INVENTORY_DATA:
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
