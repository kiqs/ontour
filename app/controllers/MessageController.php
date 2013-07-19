<?php

namespace app\controllers;

use app\models\AddMessageForm;
use app\models\AddConversationForm;
use yii;
use yii\web\Controller;
use app\models\Conversation;

class MessageController extends Controller
{
    const EVENT_SEND_MESSAGE = "sendMessage";

    private $messageText;

    public function beforeAction($action) {

        // Check user on access
        if (Yii::$app->getUser()->getIsGuest()) {
            return Yii::$app->getResponse()->redirect('@www/');
        }

        /* Add event handler
         *
         * Use example:
         * $this->messageText = "Some message text";
         * $this->trigger(self::EVENT_SEND_MESSAGE);
         */
        $this->on(self::EVENT_SEND_MESSAGE, array($this, 'sendMessageHandler'));
        return parent::beforeAction($action);
    }

    protected function sendMessageHandler($event) {
        $email = Yii::$app->getUser()->getIdentity()->email;
        $mail = Yii::$app->getComponent('mail');
        $mail->setTo($email);
        $mail->setSubject('Private message');
        $mail->setBody($this->messageText);
        $mail->send();
    }

    public function actionIndex() {
        $addConversationForm = new AddConversationForm();
        if ($addConversationForm->load($_POST) && $addConversationForm->addConversation()) {
            return Yii::$app->getResponse()->redirect('message');
        }
        // Get all users conversations
        $conversations = Yii::$app->getUser()->getIdentity()->conversations;
        return $this->render('conversations', array(
            'conversations' => $conversations,
            'model'         => $addConversationForm,
        ));

    }

    public function actionConversation($id = NULL) {

        // If function called without parameters
        if(!isset($id) || Conversation::find($id) == NULL) {
            return Yii::$app->getResponse()->redirect('message');
        }

        // Get conversation and check if current user belongs to it
        // If user is not member of conversation redirect him
        $conversation = Conversation::find($id);
        if(!($conversation -> isConversationMember(Yii::$app->getUser()->getIdentity()->id))) {
            return Yii::$app->getResponse()->redirect('message');
        }

        // Create new form object, set conversation and user id
        $addMessageForm = new AddMessageForm();
        $addMessageForm->conversation_id = $id;
        $addMessageForm->user_id = Yii::$app->getUser()->getIdentity()->id;

         // If data was successfully loaded
        if ($addMessageForm->load($_POST) && $addMessageForm->addMessage()) {
            return Yii::$app->getResponse()->redirect('message/conversation/' . $id);
        } else {
            $messages = $conversation->messages;
            return $this->render('messages', array(
                'conversationTitle' => $conversation->title,
                'messages'          => $messages,
                'model'             => $addMessageForm,
            ));
        }
    }
}