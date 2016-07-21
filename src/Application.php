<?php
namespace Xearts\SilexBase;

use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Knp\Provider\ConsoleServiceProvider;
use Silex\Application as BaseApplication;
use Silex\Application\FormTrait;
use Silex\Application\MonologTrait;
use Silex\Application\SecurityTrait;
use Silex\Application\SwiftmailerTrait;
use Silex\Application\TranslationTrait;
use Silex\Application\TwigTrait;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\CsrfServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\VarDumperServiceProvider;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Xearts\SilexBase\Doctrine\DoctrineBridgeManagerRegistry;
use Xearts\SilexBase\Provider\DoctrineMigrationProvider;
use Xearts\SilexBase\Provider\DoctrineOrmCommandProvider;


class Application extends BaseApplication
{
    use TwigTrait;
    use FormTrait;
    use SecurityTrait;
    use UrlGeneratorTrait;
    use SwiftmailerTrait;
    use MonologTrait;
    use TranslationTrait;


    public function __construct(array $values = array())
    {
        if (!isset($values['app_dir'])) {
            $values['app_dir'] = __DIR__.'/../../../app';
        }
        if (!isset($values['src_dir'])) {
            $values['src_dir'] = __DIR__.'/../../../src';
        }
        parent::__construct($values);

        $this->loadConfig();
        $this->initSession();
        $this->initTwig();
        $this->initMonolog();
        $this->initMailer();
        $this->initTranslation();
        $this->initForm();
        $this->initDb();
        $this->initConsole();
        $this->initOther();
        $this->initController();

        $this->log('test');
    }

    protected function loadConfig()
    {
        if (isset($this['config_file'])) {
            $configFile = $this['config_file'];
        } else {
            $configFile = $this['app_dir'] . '/config/config.yml';
        }

        if (!file_exists($configFile)) {
            return;
        }

        $config = Yaml::parse(file_get_contents($configFile));
        if (isset($this['config'])) {

            $this['config'] = array_replace_recursive($this['config'], $config);
        } else {
            $this['config'] = $config;
        }
    }


    protected function initTwig()
    {
        $twigPath = array();
        if (isset($this['twig.path'])) {
            if (is_array($this['twig.path'])) {
                $twigPath = $this['twig.path'];
            } else {
                $twigPath[] = $this['twig.path'];
            }
        }
        $twigPath[] = $this['app_dir'] . '/Resources/views';
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => $twigPath,
            'twig.options' => array(
                'cache' => $this['app_dir']. '/cache/views',
            ),
        ));
    }

    protected function initMonolog()
    {
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => $this['app_dir'].'/logs/app.log',
        ));
    }

    protected function initSession()
    {
        $this->register(new SessionServiceProvider());
    }


    protected function initMailer()
    {
        // メール送信時の文字エンコード指定(デフォルトはUTF-8)
        if (isset($this['config']['mail']['charset_iso_2022_jp'])) {
            if ($this['config']['mail']['charset_iso_2022_jp'] === true) {
                \Swift::init(function() {
                    \Swift_DependencyContainer::getInstance()
                        ->register('mime.qpheaderencoder')
                        ->asAliasOf('mime.base64headerencoder');
                    \Swift_Preferences::getInstance()->setCharset('iso-2022-jp');
                });
            }
        }

        $mailConfig = isset($this['config']['mail'])
            ? $this['config']['mail']
            : array(

            )
        ;

        $this->register(new SwiftmailerServiceProvider());
        $this['swiftmailer.options'] = $this['config']['mail'];

        if (isset($this['config']['mail']['spool']) && is_bool($this['config']['mail']['spool'])) {
            $this['swiftmailer.use_spool'] = $this['config']['mail']['spool'];
        }
        // デフォルトはsmtpを使用
        $transport = $this['config']['mail']['transport'];
        if ($transport == 'sendmail') {
            $this['swiftmailer.transport'] = \Swift_SendmailTransport::newInstance();
        } elseif ($transport == 'mail') {
            $this['swiftmailer.transport'] = \Swift_MailTransport::newInstance();
        }

    }

    protected function initTranslation()
    {
        $this->register(new LocaleServiceProvider());
        $this->register(new TranslationServiceProvider(), array(
            'locale_fallbacks' => array('ja'),
        ));

        $this->extend('translator', function($translator, $app) {
            $translator->addLoader('yaml', new YamlFileLoader());

            $translator->addResource('yaml', $this['app_dir'].'/Resources/locales/ja.yml', 'ja');

            return $translator;
        });
    }

    protected function initForm()
    {
        $this->register(new FormServiceProvider());
        $this->register(new CsrfServiceProvider());
        $this->register(new ValidatorServiceProvider());

        // Doctrine Brigde for form extension
        $this['form.extensions'] = $this->extend('form.extensions', function ($extensions, $app)  {
            $manager = new DoctrineBridgeManagerRegistry(
                null, array(), array('default'), null, null, '\Doctrine\ORM\Proxy\Proxy'
            );
            $manager->setContainer($app);
            $extensions[] = new DoctrineOrmExtension($manager);
            return $extensions;
        });

    }


    protected function initDb()
    {
        $this->register(new DoctrineServiceProvider(), array(
            'db.options' => $this['config']['db.options'],
        ));

        $entityConfig = $this['config']['entity'];

        $this->register(new DoctrineOrmServiceProvider, array(
            'orm.proxies_dir' => $this['app_dir'] . '/cache/doctrine/proxies',
            'orm.em.options' => array(
                'mappings' => array(
                    // Using actual filesystem paths
                    array(
                        'type' => 'annotation',
                        'namespace' => $entityConfig['namespace'],
                        'path' => $this['src_dir'].$entityConfig['path'],
                    ),
                ),
            ),
        ));
    }

    protected function initConsole()
    {
        $config = isset($this['config']['console'])
            ? $this['config']['console']
            : array(
                'name' => 'MyApplication',
                'version' => '1.0.0',
            )
        ;
        $this->register(new ConsoleServiceProvider(), array(
            'console.name' => $config['name'],
            'console.version'=> $config['version'],
            'console.project_directory' => $this['app_dir']
        ));


        $migrationPath = isset($this['config']['db.migrations.path'])
            ? $this['config']['db.migrations.path']
            : $this['app_dir'].'/Resources/migrations'
        ;
        $this->register(new DoctrineMigrationProvider(), array(
            'db.migrations.path' => $migrationPath,
        ));
        $this->register(new DoctrineOrmCommandProvider());

    }

    protected function initOther()
    {
        $this->register(new VarDumperServiceProvider());
    }

    protected function initController()
    {
        // mount controllers
        $this->register(new ServiceControllerServiceProvider());
        Request::enableHttpMethodParameterOverride(); // PUTやDELETEできるようにする

    }

}
