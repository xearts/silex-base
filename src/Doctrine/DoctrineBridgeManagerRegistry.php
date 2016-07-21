<?php
namespace Xearts\SilexBase\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Pimple\Container;
use Silex\Application;

class DoctrineBridgeManagerRegistry extends AbstractManagerRegistry
{
    /**
     * @var Container
     */
    protected $container;

    protected function getService($name)
    {
        return $this->container[$name];

    }

    protected function resetService($name)
    {
        unset($this->container[$name]);

    }

    public function getAliasNamespace($alias)
    {
        throw new \BadMethodCallException('Namespace aliases not supported.');

    }

    public function setContainer(Application $app)
    {
        $this->container = $app['orm.ems'];
    }

}
