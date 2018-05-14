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

    /**
     * @param $keyType
     * @param $accessKey
     * @param $secretKey
     */
    private function initUserCredentials($keyType, $accessKey, $secretKey)
    {
        $accessKeyEnv = getenv('ACCESS_KEY');
        $accessKey = $accessKeyEnv ? : $accessKey;

        $secretKeyEnv = getenv('SECRET_KEY');
        $secretKey = $secretKeyEnv ? : $secretKey;

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
            try {
                $res = $settingsForm->process();
            } catch (pm_Exception $e) {
                $this->stderr($e->getMessage());
                $this->exitCode(1);
            }
            if ($keyType == Modules_Route53_Form_Settings::KEY_TYPE_USER_CREDENTAL) {
                $this->stdout(pm_Locale::lmsg('userLoggedIn') . PHP_EOL);
            } else {
                $this->stdout(pm_Locale::lmsg('iamUserCreated', ['userName' => $res['userName']]) . PHP_EOL);
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
