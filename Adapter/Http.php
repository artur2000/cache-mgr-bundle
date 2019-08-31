<?php
/**
 * Created by IntelliJ IDEA.
 * User: artur
 * Date: 09.10.16
 * Time: 17:47
 */

namespace Clownfish\CacheMgrBundle\Adapter;


use App\SystemKeys;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

class Http
{

    const CACHE_LIFETIME_MICRO = 60;
    const CACHE_LIFETIME_SHORT = 300;
    const CACHE_LIFETIME_MIDDLE = 3600;
    const CACHE_LIFETIME_LONG = 3600*24;
    const CACHE_LIFETIME_EXTRA_LONG = 3600*24*7;

    protected $kernel = null;

    /**
     * Http constructor.
     */
    public function __construct($kernel) {
        $this->kernel = $kernel;
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param array $options
     * @throws \Exception
     */
    public function setPrivate(Response $response, Request $request, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        $response->prepare($request);
        $response->isNotModified($request);
        $response->setPrivate();
        $this->setOptions($response, $options);

    }

    /**
     * @param Response $response
     * @param Request $request
     * @param array $options
     * @throws \Exception
     */
    public function setPublic(Response $response, Request $request, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        $response->prepare($request);
        $response->isNotModified($request);
        $response->setPublic();
        $this->setOptions($response, $options);

    }

    /**
     * @param Response $response
     */
    public function autoCacheControlDisable(Response $response) {
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, true);
    }

    /**
     * Enable auto-cache-control.
     * With this header not present or set to false all responses in context of an initialized user session
     * will be automatically forced set to be private - hence no HTTP cache possible
     * @param Response $response
     */
    public function autoCacheControlEnable(Response $response) {
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, false);
    }

    /**
     * @param Response $response
     * @param array $options
     * @throws \Exception
     */
    public function setOptions(Response $response, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        $expirationTime = new \DateTime();
        $expirationTime->modify('+' . $options['max-age'] . ' seconds');

        $response->setETag(md5($response->getContent()));
        $response->setExpires($expirationTime);
        $response->setLastModified(new \DateTime());

        if (in_array($this->kernel->getEnvironment(), array('dev', 'test'))) {
            // set the values very low to be able to check the effect in DEV/TEST environment but
            // on the other hand to invalidate the cache quickly
            if ($response->headers->getCacheControlDirective('private') == true) {
                $response->setMaxAge(30);
            } else {
                $response->setSharedMaxAge(30);
            }
            // set a custom Cache-Control directive
            $response->headers->addCacheControlDirective('must-revalidate', true);
        } else {

            // set the private or shared max age
            if ($response->headers->getCacheControlDirective('private') == true) {
                $response->setMaxAge($options['max-age'] ? $options['max-age'] : 0);
            } else {
                $response->setSharedMaxAge($options['shared-max-age'] ? $options['shared-max-age'] : 0);
            }

            // set a custom Cache-Control directive
            $response->headers->addCacheControlDirective('must-revalidate', $options['must-revalidate'] ? $options['must-revalidate'] : true);
        }

    }

}