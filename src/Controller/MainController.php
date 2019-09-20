<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController 
{
    /**
     * @Route("/", name="landing_page")
     */
    public function landing() {
        return $this->render(
            'landing.html.twig'
        );
    }

    /**
     * @Route("/client", name="client_page")
     */
    public function clientLanding() {
        return $this->render(
            'client.html.twig'
        );
    }

    /**
     * @Route("/custumer-specialist", name="custumer_specialist_page")
     */
    public function custumerSpecialist() {
        return $this->render(
            'specialist.html.twig'
        );
    }
}