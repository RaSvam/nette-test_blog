<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private $database;
    public function __construct(Nette\Database\Context $database)
    {
        //Nette database connection using DI
        $this->database= $database;

    }
    public function renderDefault()
    {
        //rendering of posts
        $this->template->posts = $this->database->table('posts')
            ->order('created_at DESC');
            //->limit(3);
    }
    public function afterRender()
    {
        //ajaxing flash messages
        if ($this->isAjax() && $this->hasFlashSession())
            $this->redrawControl('flashes');
    }
    protected function createComponentPostForm()
    {
        //post form factory and assigning ajax class
        $form = new Form;
        $form->getElementPrototype()->setAttribute('class','ajax');
        $form->addText('title', 'Title:')
            ->setRequired();
        $form->addText( 'name', 'Name:');
        $form->addEmail('email', 'Email address: ')->setDefaultValue('@')->setRequired();

        $form->addTextArea('content', 'Content:')
            ->setRequired();
        $form->addSubmit('send', 'Save & publish')->setAttribute('class', 'btn btn-default');

        //if successful, call postFormSucceeded
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }
    public function postFormSucceeded($form, array $values){
        //creates new log and stream if posting form succeeded
        $log = new Logger('posts');
        $log->pushHandler(new StreamHandler('c:\xampp\htdocs\nette-blog\logs\posts.log'), Logger::INFO);

        //get post_id of the post
        $post_id = $this->getParameter('post_id');

        //if post with post_id exists, update his value
        if ($post_id) {
            $post = $this->database->table('posts')->get($post_id);
            $post->update($values);
            $this->flashMessage('The post has been edited.', 'success');
        }
        //else create new post with post_id value
        else {
            $post = $this->database->table('posts')->insert($values);
            $this->flashMessage('New post has been published.', 'success');
        }
        //if it has class ajax, redraw list and form snippets
        if ($this->isAjax()){
            $this->redrawControl('list');
            $this->redrawControl('form');
            //reset values in the form
            $form->setValues(array(), True);
        }
        //else redirect to this page
        else {
            $this->redirect('this');
        }
        //log into the streamhandler stream
        $log->info("New post created",$values);


    }



}
