<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Form\SearchClientType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController 
{
    /**
     * @Route("/", name="landing_page")
     */
    public function landing() 
    {
        return $this->render(
            'landing.html.twig'
        );
    }

    /**
     * @Route("/new-client", name="new_client_page")
     */
    public function newClient(Request $request) 
    {
        $client = new Client();

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($client);
            $em->flush();

            $this->addFlash(
                'success',
                "Sveikiname, jūs sėkmingai užsiregistravote pas specialistą, jūsų numeris: " . $client->getId()
            );

            return $this->redirectToRoute('new_client_page');
        }

        return $this->render(
            'new_client.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/customer-specialist", name="customer_specialist_page")
     */
    public function customerSpecialist() 
    {
        $allClients = $this->getDoctrine()->getRepository(Client::class)->findWaitingClients();
        $nextClient = reset($allClients);

        return $this->render(
            'specialist.html.twig', [
                'allClients' => $allClients,
                'nextClient' => $nextClient
            ]
        );
    }

    /**
     * @Route("/start-visit/{id}", name="start_visit")
     */
    public function startVisit($id) 
    {
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        $client->setStartedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('customer_specialist_page');

    }

    /**
     * @Route("/end-visit/{id}", name="end_visit")
     */
    public function endVisit($id)
    {
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $client->setFinishedAt(new \DateTime());
        $startTime = $client->getStartedAt();
        $endTime = $client->getFinishedAt();
        $duration = $startTime->diff($endTime);
        $client->setDuration($duration->format('%H:%I:%S'));
        $client->setCompleted(true);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->redirectToRoute('customer_specialist_page');

    }

    /**
     * @Route("/clients-list", name="client_list_page")
     */
    public function clientList()
    {
        $waitingClients = $this->getDoctrine()->getRepository(Client::class)->findWaitingClients();

        return $this->render(
            'client_list.html.twig', [
                'waitingClients' => $waitingClients
            ]
        );
    }

    /**
     * @Route("/client-page", name="client_page")
     */
    public function clientPage(Request $request)
    {
        $form = $this->createForm(SearchClientType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clientId = $form->getData()['id'];
            $client = $this->getDoctrine()->getRepository(Client::class)->find($clientId);

            if ($client->getCompleted() != true) {
                $completedClients = $this->getDoctrine()->getRepository(Client::class)->findBy(['completed' => true]);
                $visitDurations = [];

                foreach ($completedClients as $completedClient) {
                    $duration = $completedClient->getDuration();
                    array_push($visitDurations, $duration);
                }

                $avgDuration = date('H:i:s', array_sum(array_map('strtotime', $visitDurations)) / count($visitDurations));

                $waitingClients = $this->getDoctrine()->getRepository(Client::class)->findBy(['completed' => null]);

                $queuePosition = array_search($client, $waitingClients);

                if ($queuePosition == 0) {
                    $this->addFlash(
                        'success',
                        "Iki vizito jums apytiksliai liko laukti " . $avgDuration
                    );

                    return $this->redirectToRoute('client_page');
                } else {
                    $seconds = strtotime("1970-01-01 $avgDuration UTC");
                    $multiply = $seconds * ($queuePosition + 1);
                    $avgDuration = gmdate("H:i:s",$multiply);

                     $this->addFlash(
                        'success',
                        "Iki vizito jums apytiksliai liko laukti " . $avgDuration
                    );

                    return $this->redirectToRoute('client_page');
                }

            } else {
                $this->addFlash(
                    'success',
                    "Klientas jau aptarnautas!"
                );

                return $this->redirectToRoute('client_page');
            }

        }

        return $this->render(
            'client.html.twig', [
                'form' => $form->createView(),
            ]
        );
    }
}