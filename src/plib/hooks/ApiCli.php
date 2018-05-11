<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_ApiCli extends pm_Hook_ApiCli
{
    public function initCommand($root, $user, $clear, $accessKey = '', $secretKey = '')
    {
        if (isset($clear)) {
            $this->clear();
            return;
        }
        if (isset($root)) {
            $this->initUserCredentials(Modules_Route53_Form_Settings::KEY_TYPE_ROOT_CREDENTAL, $accessKey, $secretKey);
            return;
        }
        if (isset($user)) {
            $this->initUserCredentials(Modules_Route53_Form_Settings::KEY_TYPE_USER_CREDENTAL, $accessKey, $secretKey);
            return;
        }
        $this->stderr(pm_Locale::lmsg('cli.commands.init.wrongSyntax'));
        $this->exitCode(1);
    }

    public function getAvailableOptions($command)
    {
        $options = parent::getAvailableOptions($command);
        if (pm_ProductInfo::isUnix() && 'init' == $command) {
            $options = array_diff($options, ['access-key', 'secret-key']);
        }
        return $options;
    }

    public function helpCommand()
    {
        ob_start();
        parent::helpCommand();
        $help = ob_get_contents();
        ob_clean();

        $initCommandKey = pm_ProductInfo::isUnix() ? 'Unix' : 'Win';
        $help = str_replace('[[cli.commands.init]]', pm_Locale::lmsg("cli.commands.init{$initCommandKey}"), $help);
        $this->stdout($help);
    }

    /**
     * @param $accessKey
     * @param $secretKey
     * @param $keyType
     */
    private function initUserCredentials($keyType, $accessKey, $secretKey)
    {
        if (pm_ProductInfo::isUnix()) {
            $accessKey = getenv('ACCESS_KEY');
            $secretKey = getenv('SECRET_KEY');
        }
        $settingsForm = new Modules_Route53_Form_Settings([
            'isConsole' => true
        ]);

        $params = [
            'keyType' => $keyType,
            'key' => $accessKey,
            'secret' => $secretKey,
            'enabled' => true,
        ];

        if ($settingsForm->isValid($params)) {
            $res = $settingsForm->process();
            if ($keyType == Modules_Route53_Form_Settings::KEY_TYPE_USER_CREDENTAL) {
                $this->stdout(pm_Locale::lmsg('userLoggedIn') . PHP_EOL);
            } else {
                if ($res) {
                    $this->stdout(pm_Locale::lmsg('iamUserCreated', ['userName' => $res['userName']]) . PHP_EOL);
                } else {
                    $message = '';
                    foreach (pm_View_Status::getAllMessages(false) as $msg) {
                        if ($msg['status'] != pm_View_Status::STATUS_ERROR) {
                            continue;
                        }
                        $message .= $msg['content'] . PHP_EOL;
                    }
                    $this->stderr($message);
                    $this->exitCode(1);
                }
            }

        } else {
            $message = pm_Locale::lmsg('userLoggedInError') . PHP_EOL;
            $formMessages = $settingsForm->getMessages();
            foreach ($formMessages as $key => $errors) {
                $message .= $settingsForm->getElement($key)->getLabel() . PHP_EOL . implode(PHP_EOL, $errors) . PHP_EOL;
            }
            $message .= pm_Locale::lmsg('cliValidationFailed');
            $this->stderr($message);
            $this->exitCode(1);
        }
    }

    /**
     *
     */
    private function clear()
    {
        pm_Settings::clean();
        $this->stdout(pm_Locale::lmsg('cliClearSuccess') . PHP_EOL);
    }
}
