<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model
{
    public $email;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist',
                'targetClass' => '\common\models\User',
                'filter' => ['status' => User::STATUS_ACTIVE],
                'message' => 'There is no user with this email address.'
            ],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public function sendEmail()
    {

        try {
            
            /* @var $user User */
            $user = User::findOne([
                'status' => User::STATUS_ACTIVE,
                'email' => $this->email,
            ]);

            if (!$user) {
                return false;
            }
            
            if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->generatePasswordResetToken();
                if (!$user->save()) {
                    return false;
                }
            }

            \Yii::$app
                ->mail
                // ->compose(
                //     ['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
                //     ['user' => $user]
                // )
                ->compose('passwordResetToken-html', ['user' => $user])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' Admin'])
                ->setTo($this->email)
                ->setSubject('Password reset for ' . Yii::$app->name)
                ->send();

            return array('code' => 1);

        } catch (Exception $e) {
            return array('code' => 0, 'message' => $e->getMessage());            
        }

    }
}