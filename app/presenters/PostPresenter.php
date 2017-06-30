<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class PostPresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
        //Nette database connection
    }

    public function renderShowSingle($post_id)
    {
        //render all posts from the database
        $post=$this->database->table('posts')->get($post_id);
        if (!$post) {
            $this->error('Page not found');
        }

        $this->template->post = $post;
        $this->template->comments = $post->related('comment')->order('timestamp');
    }

    protected function createComponentCommentForm()
    {
        //comment form factory
        $form = new Form; // means Nette\Application\UI\Form

        $form->addText('name', 'Name:')
            ->setRequired();

        $form->addEmail('email', 'Email:');

        $form->addTextArea('content', 'Comment:')
            ->setRequired();

        $form->addSubmit('send', 'Send comment');
        $form->onSuccess[] = [$this, 'commentFormSucceeded'];
        return $form;
    }

    public function commentFormSucceeded($form, $values)
    {
        //called on successful posting of a comment
        $post_id = $this->getParameter('post_id');
        $this->database->table('comments')->insert([
            'post_id' => $post_id,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ]);

        $this->flashMessage('Thank you for your comment', 'success');
        $this->redirect('this');
    }
    protected function createComponentPostForm()
    {
        //post form factory
        $form = new Form;
        $form->addText('title', 'Title:')
            ->setRequired();
        $form->addText( 'name', 'Your Name');

        $form->addTextArea('content', 'Content:')
            ->setRequired();
        $form->addSubmit('send', 'Save & publish');

        //if successful, call on postFormSucceeded
        $form->onSuccess[] = [$this, 'postFormSucceeded'];

        return $form;
    }
    public function postFormSucceeded($form, $values){
        //creates new log and stream + name for it
        $log = new Logger('posts');
        $log->pushHandler(new StreamHandler('c:\xampp\htdocs\nette-blog\logs\posts.log'), Logger::INFO);

        //called on successful posting of a post
        $post_id = $this->getParameter('post_id');
        //if post with post_id exists, update his value
        if ($post_id) {
            $post = $this->database->table('posts')->get($post_id);
            $post->update($values);
        }
        //else create new post with post_id value
        else {
            $post = $this->database->table('posts')->insert($values);
        }
        $this->flashMessage('New post has been published.', 'success');
        //log into the streamhandler stream
        $log->info("New post created");
        $this->redirect('showSingle', $post->id);
    }
    public function actionEditArticle($post_id)
    {
        //used to edit articles with {action} macro
        $post = $this->database->table('posts')->get($post_id);
        if(!$post_id){
            $this->error("No article found");

    }
        //content is saved into the existing post_content
        $this['postForm']->setDefaults($post->toArray());
    }

}