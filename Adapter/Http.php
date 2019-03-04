<?php
/**
 * Created by IntelliJ IDEA.
 * User: artur
 * Date: 09.10.16
 * Time: 17:47
 */

namespace Clownfish\CacheMgrBundle\Adapter;


use Symfony\Component\HttpFoundation\Response;

class Http
{

    protected $kernel = null;

    /**
     * Http constructor.
     */
    public function __construct($kernel) {
        $this->kernel = $kernel;
    }

    /**
     * @param Response $response
     * @param array $options
     */
    public function setPrivate(Response $response, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        $response->setPrivate();
        $this->setOptions($response, $options);

    }

    /**
     * @param Response $response
     * @param array $options
     */
    public function setPublic(Response $response, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        $response->setPublic();
        $this->setOptions($response, $options);

    }

    /**
     * @param Response $response
     * @param array $options
     */
    public function setOptions(Response $response, $options = array('max-age'=>3600, 'shared-max-age'=>3600, 'must-revalidate'=>true)) {

        if (in_array($this->kernel->getEnvironment(), array('dev', 'test'))) {
            // set the values very low to be able to check the effect in DEV/TEST environment but
            // on the other hand to invalidate the cache quickly
            if ($response->headers->getCacheControlDirective('private') == true) {
                $response->setMaxAge(10);
            } else {
                $response->setSharedMaxAge(10);
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