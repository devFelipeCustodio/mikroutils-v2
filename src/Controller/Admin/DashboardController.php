<?php

namespace App\Controller\Admin;

use App\Entity\ClientDetail;
use App\Entity\ClientExport;
use App\Entity\ClientSearch;
use App\Entity\LogSearch;
use App\Entity\User;
use App\Entity\UserSession;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_user_index');
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administração');
    }


    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToRoute('Mikroutils', 'fa fa-home', 'app_client_search'),
            MenuItem::linkToCrud('Usuários', 'fa fa-user', User::class),
            MenuItem::linkToCrud('Sessões', 'fa fa-desktop', UserSession::class),
            MenuItem::section("Pesquisas"),
            MenuItem::linkToCrud('Clientes', 'fa fa-users', ClientSearch::class),
            MenuItem::linkToCrud('Visualizações', 'fa fa-circle-info', ClientDetail::class),
            MenuItem::linkToCrud('Logs', 'fa fa-file', LogSearch::class),
            MenuItem::section("Exportações"),
            MenuItem::linkToCrud('Clientes', 'fa fa-users', ClientExport::class),
        ];
    }
}
