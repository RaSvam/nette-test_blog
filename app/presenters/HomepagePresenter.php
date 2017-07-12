<?php

namespace App\Presenters;

use Nette;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private $database;
    /** @var \PostForm @inject */
    public $postFormFactory;

    public function __construct(Nette\Database\Context $database)
    {
        //Nette database connection
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
        //creates post form using a factory
        $form = $this->postFormFactory->create(1);
        //if successful, call postFormSucceeded
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }
    public function postFormSucceeded($form, array $values){
        //creates new log and stream if posting form succeeded
        $log = new Logger('posts');
        $log->pushHandler(new StreamHandler('/log/posts.log'), Logger::INFO);

        $post = $this->database->table('posts')->insert($values);
        $this->flashMessage('New post has been published.', 'success');

        //if post has class ajax, redraw snippets
        if ($this->isAjax()){
            $this->redrawControl('list');
            $this->redrawControl('form');
            //reset values in the form
            $form->setValues(array(), True);
        }
        //else redirects to this page
        else {
            $this->redirect('this');
        }
        //logs into the streamhandler stream
        $log->info("New post created",$values);


    }



}
