<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
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
     * @Route("/client", name="client_page")
     */
    public function clientLanding(Request $request) 
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

            return $this->redirectToRoute('client_page');
        }

        return $this->render(
            'client.html.twig', [
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
        return $this->render(
            'client_list.html.twig'
        );
    }
}