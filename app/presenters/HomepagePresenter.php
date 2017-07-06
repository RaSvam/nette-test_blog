<?php

namespace App\Presenters;

use Nette;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private $database;

    private $postFormFactory;
    public function __construct(Nette\Database\Context $database, \PostForm $postFormFactory)
    {
        //Nette database connection using DI
        $this->database= $database;
        $this->postFormFactory = $postFormFactory;

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
        $form = $this->postFormFactory->create(1);
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
