<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_ApiCli extends pm_Hook_ApiCli
{
    public function initCommand($root, $user, $accessKey, $secretKey, $clear)
    {
        if (isset($clear)) {
            $this->clear();
            return;
        }
        if (isset($root)) {
            $this->initUserCredentials($accessKey, $secretKey, Modules_Route53_Form_Settings::KEY_TYPE_ROOT_CREDENTAL);
            return;
        }
        if (isset($user)) {
            $this->initUserCredentials($accessKey, $secretKey, Modules_Route53_Form_Settings::KEY_TYPE_USER_CREDENTAL);
            return;
        }
    }

    /**
     * @param $accessKey
     * @param $secretKey
     * @param $keyType
     */
    private function initUserCredentials($accessKey, $secretKey, $keyType)
    {
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
                $this->stdout( pm_Locale::lmsg('userLoggedIn') . PHP_EOL);
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
        $this->stdout( pm_Locale::lmsg('cliClearSuccess') . PHP_EOL);
    }
}
