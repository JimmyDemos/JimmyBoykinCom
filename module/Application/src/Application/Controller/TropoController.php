<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Tropo;
use SessionAPI;
/**
 * TropoController
 *
 * @author
 *
 * @version
 *
 */
 
class TropoController extends AbstractActionController
{

    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('config');
        $token = $config['tropotextkey'];
        $tropo = new Tropo();//to get sessionapi to autoload hack
        $session = new SessionAPI();
        $sessionid = $session->createSession($token);
        
        $this->pushText($sessionid, '19199438138', 'Whoop!');
        
        $viewArray = array('sid'=>$sessionid);
        
        return $viewArray;
    }
    
    public function textAction()
    {
        $post = json_decode($this->getRequest()->getContent(), true);
        //print(var_export($post, true));
        if(!empty($post['session']['id'])){
            $sessionid = $post['session']['id'];
            //print("session id: " . $sessionid);
        } else {
            //echo("No session provided!");
        }
        
        if(!empty($post['session']['parameters']['action']) && $post['session']['parameters']['action'] == 'create'){
            $text = $this->popText($sessionid);
            $tropo = new Tropo();
            $tropo->call($text['phone'], array('network'=>'SMS'));
            $tropo->say($text['message']);
            $result = json_decode($tropo->__toString(), TRUE);
            $model = new JsonModel($result);
            return $model;
        }
        
        

    }
    
    public function voiceAction()
    {
        $tropo = new Tropo();
        $tropo->setVoice('Victor');
        $tropo->say('There\'s not much to see here!');
        $tropo->hangup();
        $result = json_decode($tropo->__toString(), TRUE);
        $model = new JsonModel($result);
        return $model;
    }
    
    private function popText($sessionid)
    {
        $file = file_get_contents('textqueue.txt');
        $textQueue = unserialize($file);
        $text['phone'] = $textQueue[$sessionid]['phone'];
        $text['message'] = $textQueue[$sessionid]['message'] ;
        unset($textQueue[$sessionid]);
        $textQueue = serialize($textQueue);
        file_put_contents('textqueue.txt', $textQueue);
        return $text;
    }
    
    private function pushText($sessionid, $phone, $message)
    {
        $file = file_get_contents('textqueue.txt');
        $textQueue = unserialize($file);
        $textQueue[$sessionid]['message'] = $message;
        $textQueue[$sessionid]['phone'] = $phone;
        $textQueue = serialize($textQueue);
        file_put_contents('textqueue.txt', $textQueue);
    }
    
}