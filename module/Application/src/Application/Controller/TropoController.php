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
        $demoid = rand(1,99999);

        $viewArray = array('demoid'=>$demoid);
        
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

        /** If message is outgoing **/
        if(!empty($post['session']['parameters']['action']) && $post['session']['parameters']['action'] == 'create'){
            $text = $this->popText($sessionid);
            $tropo = new Tropo();
            $tropo->call($text['phone'], array('network'=>'SMS'));
            $tropo->say($text['message']);
            $result = json_decode($tropo->__toString(), TRUE);
            $model = new JsonModel($result);
            return $model;
        }

        /** Message is incoming **/
        $phone = $post['session']['from']['id'];
        $content = $post['session']['initialText'];
        
        if(is_numeric($content)){
            $demoid = $content;
            //demoid verification
            $this->pushDemo($demoid, $phone, 'Guest');
            die('ok authed');
        } elseif(strcasecmp('yes',$content) == 0) {
            $demoid = $this->getDemoIDByPhone($phone);
            $this->pushDemo($demoid, $phone, 'Guest', 'Safe!');
            die('ok safe');
        } elseif(strcasecmp('no',$content) == 0) {
            $demoid = $this->getDemoIDByPhone($phone);
            $this->pushDemo($demoid, $phone, 'Guest', 'Help!');
            die('ok help');
        }
        
    }

    
    public function demoDataAction()
    {
        $demoid = $this->params('param');
        $demodata = $this->getDemo($demoid);
        $model = new JsonModel($demodata);
        return $model;
    }
    
    public function alertAction()
    {
        $demoid = $this->params('param');
        $demodata = $this->getDemo($demoid);    
        $phone = $demodata['phone'];
        
        $config = @$this->getServiceLocator()->get('config');
        $token = $config['tropotextkey'];
        $tropo = new Tropo();//to get sessionapi to autoload hack
        $session = new SessionAPI();
        $sessionid = $session->createSession($token);
        $this->pushText($sessionid, $phone, 'THIS IS A TEST ALERT!');
        die('ok');
    }
    
    public function drillAction()
    {
        $demoid = $this->params('param');
        $demodata = $this->getDemo($demoid);
        $phone = $demodata['phone'];
        
        $config = @$this->getServiceLocator()->get('config');
        $token = $config['tropotextkey'];
        $tropo = new Tropo();//to get sessionapi to autoload hack
        $session = new SessionAPI();
        $sessionid = $session->createSession($token);
        $this->pushText($sessionid, $phone, 'THIS IS A TEST EMERGENCY DRILL! Are you safe? Please respond YES or NO');
        die('ok');  
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
    
    
    private function getDemo($demoid)
    {
        $file = file_get_contents('demo.txt');
        $demo = unserialize($file);
        if(!empty($demo[$demoid]))
            return $demo[$demoid];
        return array();
    }
    
    private function getDemoIDByPhone($phone)
    {
        $file = file_get_contents('demo.txt');
        $demo = unserialize($file);
        
        foreach($demo as $key => $needle){
            if($needle['phone'] == $phone)
                return $key;
        }
       return false;
    }
    
    private function pushDemo($demoid, $phone, $name, $status = '')
    {
        $file = file_get_contents('demo.txt');
        $demo = unserialize($file);
        
        //Delete any other demos by this number
        $oldDemoKey = $this->getDemoIDByPhone($phone);
        if($oldDemoKey)
            unset($demo[$oldDemoKey]);

        
        $demo[$demoid]['phone'] = $phone;
        $demo[$demoid]['name'] = $name;
        $demo[$demoid]['status'] = $status;
        $demo = serialize($demo);
        file_put_contents('demo.txt', $demo);
    }
    
    
}