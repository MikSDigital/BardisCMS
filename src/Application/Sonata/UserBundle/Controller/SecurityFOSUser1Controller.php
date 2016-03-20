<?php

/*
 * Sonata User Bundle Overrides
 * This file is part of the BardisCMS.
 * Manage the extended Sonata User entity with extra information for the users
 *
 * (c) George Bardis <george@bardis.info>
 *
 */

namespace Application\Sonata\UserBundle\Controller;


use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\SecurityContext;

use BardisCMS\PageBundle\Entity\Page as Page;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller managing the user login
 */
class SecurityFOSUser1Controller extends Controller
{
    // Adding variables required for the rendering of pages
    protected $container;
    private $page;
    private $publishStates;
    private $userName;
    private $settings;
    private $serveMobile;
    private $userRole;
    private $enableHTTPCache;
    private $isAjaxRequest;

    const LOGIN_PAGE_ALIAS = 'login';

    /**
     * Override the ContainerAware setContainer to accommodate the extra variables
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;

        // Setting the scoped variables required for the rendering of the page
        $this->page = null;
        $this->userName = null;

        // Get the settings from setting bundle
        $this->settings = $this->get('bardiscms_settings.load_settings')->loadSettings();

        // Get the highest user role security permission
        $this->userRole = $this->get('sonata_user.services.helpers')->getLoggedUserHighestRole();

        // Check if mobile content should be served
        $this->serveMobile = $this->get('bardiscms_mobile_detect.device_detection')->testMobile();

        // Set the flag for allowing HTTP cache
        $this->enableHTTPCache = $this->container->getParameter('kernel.environment') == 'prod' && $this->settings->getActivateHttpCache();

        // Check if request was Ajax based
        $this->isAjaxRequest = $this->get('bardiscms_page.services.ajax_detection')->isAjaxRequest();

        // Set the publish statuses that are available for the user
        $this->publishStates = $this->get('bardiscms_page.services.helpers')->getAllowedPublishStates($this->userRole);

        // Get the logged user if any
        $logged_user = $this->get('sonata_user.services.helpers')->getLoggedUser();
        if (is_object($logged_user) && $logged_user instanceof UserInterface) {
            $this->userName = $logged_user->getUsername();
        }
    }

    /**
     * Rendering of the login page
     *
     * @return Response
     */
    public function loginAction()
    {

        $user = $this->get('sonata_user.services.helpers')->getLoggedUser();

        if ($user instanceof UserInterface) {
            $this->container->get('session')->getFlashBag()->set('sonata_user_error', 'sonata_user_already_authenticated');
            $url = $this->container->get('router')->generate('sonata_user_profile_show');

            if($this->isAjaxRequest) {
                return $this->onAjaxSuccess($url);
            }

            return new RedirectResponse($url);
        }

        $this->page = $this->getDoctrine()->getRepository('PageBundle:Page')->findOneByAlias($this::LOGIN_PAGE_ALIAS);

        if (!$this->page) {
            return $this->get('bardiscms_page.services.show_error_page')->errorPageAction(Page::ERROR_404);
        }

        // Simple publishing ACL based on publish state and user Allowed Publish States
        $accessAllowedForUserRole = $this->get('bardiscms_page.services.helpers')->isUserAccessAllowedByRole(
            $this->page->getPublishState(),
            $this->publishStates
        );

        if(!$accessAllowedForUserRole){
            return $this->get('bardiscms_page.services.show_error_page')->errorPageAction(Page::ERROR_401);
        }

        // TODO: check if the login page should be cached or not before allowing cache here
        // Return cached page if enabled
        /*
        if ($this->enableHTTPCache) {

            //$response = $this->setResponseCacheHeaders(new Response());
            $response = $this->get('bardiscms_page.services.http_cache_headers_handler')->setResponseCacheHeaders(null, $this->page->getDateLastModified(), true, 3600);

            if (!$response->isNotModified($this->pageRequest)) {
                // Marks the Response stale
                $response->expire();
            } else {
                // return the 304 Response immediately
                return $response;
            }
        }
        */

        $this->page = $this->get('bardiscms_settings.set_page_settings')->setPageSettings($this->page);

        $request = $this->container->get('request');
        $session = $request->getSession();

        // Get the login error if any
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        // Get the error messages
        if ($error) {
            // If the request was Ajax based and the registration was not successful
            if($this->isAjaxRequest){
                return $this->onAjaxError($error);
            }

            $error = $error->getMessage();
        }

        // Get last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        $csrfToken = $this->container->has('form.csrf_provider') ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate') : null;

        $pageData = array(
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
            'page' => $this->page,
            'mobile' => $this->serveMobile,
            'logged_username' => $this->userName
        );

        // Render login page
        $response = $this->render('SonataUserBundle:Security:login.html.twig', $pageData);

        // TODO: check if the login page should be cached or not before allowing cache here
        /*
        if ($this->enableHTTPCache) {
            //$response = $this->setResponseCacheHeaders($response);
            $response = $this->get('bardiscms_page.services.http_cache_headers_handler')->setResponseCacheHeaders($response, true, 3600);
        }
        */

        return $response;
    }

    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    /**
     * Extend with new method to handle Ajax response with errors
     *
     * @param string $error
     *
     * @return Response
     */
    protected function onAjaxError($error)
    {
        $errorList = array($error->getMessage());
        $formMessage = $this->get('translator')->trans(
            $error->getMessage(),
            array(),
            "SonataUserBundle"
        );
        $formHasErrors = true;

        $ajaxFormData = array(
            'errors' => $errorList,
            'formMessage' => $formMessage,
            'hasErrors' => $formHasErrors
        );

        $ajaxFormResponse = new Response(json_encode($ajaxFormData));
        $ajaxFormResponse->headers->set('Content-Type', 'application/json');

        return $ajaxFormResponse;
    }

    /**
     * Extend with new method to handle Ajax response with success
     *
     * @param String $url
     *
     * @return Response
     */
    protected function onAjaxSuccess($url)
    {
        $errorList = array();
        $formMessage = '';
        $formHasErrors = false;
        $redirectURL = $url;

        $ajaxFormData = array(
            'errors' => $errorList,
            'formMessage' => $formMessage,
            'hasErrors' => $formHasErrors,
            'redirectURL' => $redirectURL
        );

        $ajaxFormResponse = new Response(json_encode($ajaxFormData));
        $ajaxFormResponse->headers->set('Content-Type', 'application/json');

        return $ajaxFormResponse;
    }
}
