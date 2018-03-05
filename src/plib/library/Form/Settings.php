<?php
// Copyright 1999-2018. Plesk International GmbH.

require_once (__DIR__ . '/../../vendor/autoload.php');

class Modules_Route53_Form_Settings extends pm_Form_Simple
{
    const USERNAME_PREFIX = 'plesk-route53-';
    const USER_POLICY_ACTIONS = ['route53:*', 'route53domains:*'];

    const KEY_TYPE_ROOT_CREDENTAL = 'rootCredential';
    const KEY_TYPE_USER_CREDENTAL = 'userCredential';

    private $isConsole = false;

    public function __construct($options = [])
    {
        if (!empty($options['isConsole'])) {
            $this->isConsole = $options['isConsole'];
        }

        parent::__construct($options);
    }

    public function init()
    {
        parent::init();

        $this->addElement('description', 'description', [
            'description' =>
                '<ul>' .

            '<li>' . pm_Locale::lmsg('getAuth') . ' : ' .
            '<a href="https://aws.amazon.com/" target="_blank">https://aws.amazon.com</a> ' .
            '-&gt; MyAccount -&gt; Security Credentials </li>' .
            '<li>' . pm_Locale::lmsg('getAuthStepTwo', [
                'learnMoreUrl' => 'https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/access-control-managing-permissions.html'
             ]) . '</li>',
            'escape' => false,
        ]);

        $this->addElement('radio', 'keyType', [
            'label' => pm_Locale::lmsg('awsKeyType'),
            'multiOptions' => [
                self::KEY_TYPE_ROOT_CREDENTAL => pm_Locale::lmsg('formRootCredential'),
                self::KEY_TYPE_USER_CREDENTAL => pm_Locale::lmsg('formPreCreatedLimitedUserCredential')
            ],
            'value' => pm_Settings::get('keyType', self::KEY_TYPE_ROOT_CREDENTAL),
            'required' => true,
            'validators' => [
                ['NotEmpty', true],
            ],
        ]);

        $this->addElement('text', 'key', array(
            'label' => pm_Locale::lmsg('keyLabel'),
            'value' => pm_Settings::get('key'),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $this->addElement('text', 'secret', array(
            'label' => pm_Locale::lmsg('secretLabel'),
            'value' => pm_Settings::get('secret'),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $this->addElement('checkbox', 'enabled', array(
            'label' => pm_Locale::lmsg('enabledLabel'),
            'value' => pm_Settings::get('enabled'),
        ));

        if (!$this->isConsole) {
            $this->addControlButtons(array(
                'sendTitle' => pm_Locale::lmsg('login'),
                'cancelLink' => pm_Context::getModulesListUrl(),
            ));
        }

    }

    public function isValid($data)
    {
        if ($data['enabled']) {
            try {
                if ($data['keyType'] == self::KEY_TYPE_ROOT_CREDENTAL) {
                    $res = $this->isAdministratorAccess($data['key'], $data['secret']);
                    if (!$res) {
                        throw new Exception(pm_Locale::lmsg('notAdministratorAccess'));
                    }
                } else {
                    Modules_Route53_Client::factory([
                        'credentials' => [
                            'key' => $data['key'],
                            'secret' => $data['secret'],
                        ]
                    ])->checkCredentials();
                }

            } catch (Exception $e) {
                $this->markAsError();
                $this->getElement('key')->addError($e->getMessage());
                $this->getElement('secret')->addError($e->getMessage());
                return false;
            }
        } else {
            $this->getElement('key')->setRequired(false);
            $this->getElement('secret')->setRequired(false);
        }

        return parent::isValid($data);
    }

    public function process()
    {
        $res = [];

        pm_Settings::set('enabled', $this->getValue('enabled'));

        $keyType = $this->getValue('keyType');
        $key = $this->getValue('key');
        $secret = $this->getValue('secret');

        if ($keyType == self::KEY_TYPE_USER_CREDENTAL) {
            $this->saveUserData($key, $secret);
        } else {
            $res = $this->createUser($key, $secret);
            if ($res) {
                $this->saveUserData($res['key'], $res['secret']);
            }
        }
        return $res;
    }

    private function saveUserData($key, $secret)
    {
        pm_Settings::set('key', $key);
        pm_Settings::set('secret', $secret);
        pm_Settings::set('keyType', self::KEY_TYPE_USER_CREDENTAL);
    }

    private function isAdministratorAccess($key, $secret)
    {
        $iamComponent = new AmazonIAM();
        $iamComponent
            ->setKey($key)
            ->setSecret($secret)
        ;
        $res = $iamComponent->isAdministratorAccess();
        return $res;
    }

    /**
     * @param $key
     * @param $secret
     *
     * @return array|bool
     */
    private function createUser($key, $secret)
    {
        $res = false;
        try {
            $iamComponent = new AmazonIAM();
            $iamComponent->setKey($key)->setSecret($secret);
            $userName = $iamComponent->generateIAMUserName(self::USERNAME_PREFIX);

            $iamComponent->createUser($userName);

            $policyDocument = $iamComponent->createPolicyDocument(self::USER_POLICY_ACTIONS);

            $iamComponent->getIAMClient()
                ->putUserPolicy([
                'PolicyDocument' => $policyDocument->__toString(),
                'PolicyName' => self::USERNAME_PREFIX .'full-access',
                'UserName' => $userName
            ]);

            $response = $iamComponent->createAccessKey($userName);
            $responseAccessKey = $response->get('AccessKey');
            pm_View_Status::addInfo(pm_Locale::lmsg('iamUserCreated', ['userName' => $userName]));
            $res = [
                'userName' => $userName,
                'key' => $responseAccessKey['AccessKeyId'],
                'secret' => $responseAccessKey['SecretAccessKey'],
            ];
        } catch (\Aws\Exception\AwsException $e) {
            pm_View_Status::addError($e->getAwsErrorMessage());
        }

        return $res;
    }
}
