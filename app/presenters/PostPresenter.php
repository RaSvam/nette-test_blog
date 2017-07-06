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
        //Nette database connection using DI
        $this->database = $database;

    }

    public function renderShowSingle($post_id)
    {
        //render all posts from the database
        $post=$this->database->table('posts')->get($post_id);

        //if post with post_id doesn't exist, throw an error
        if (!$post) {
            $this->error('Page not found');
        }
        //render single post and related comments (1:n post - comment)
        $this->template->post = $post;
        $this->template->comments = $post->related('comment')->order('timestamp');
    }


    protected function createComponentCommentForm()
    {
        //comment form factory
        $form = new Form; // Nette\Application\UI\Form

        $form->addText('name', 'Name:')
            ->setRequired();

        $form->addEmail('email', 'Email:')->setDefaultValue('@');
        $form->addTextArea('content', 'Comment:')
            ->setRequired();

        $form->addSubmit('send', 'Send comment')->setAttribute('class', 'btn btn-default');
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
        //post form factory and assign ajax class
        $form = new Form;
        $form->addText('title', 'Title of the post:')
            ->setRequired();
        $form->addText( 'name', 'Your Name');
        $form->addEmail('email', 'Your email address: ')->setDefaultValue('@')->setRequired();

        $form->addTextArea('content', 'Content:')
            ->setRequired();
        $form->addSubmit('send', 'Save & publish')->setAttribute('class', 'btn btn-default');

        //if successful, call postFormSucceeded
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
        return $form;
    }
    public function postFormSucceeded($form, array $values)
    {
        //creates new log and stream
        $log = new Logger('posts');
        $log->pushHandler(new StreamHandler('c:\xampp\htdocs\nette-blog\logs\posts.log'), Logger::INFO);

        //called on successful posting of a post
        $post_id = $this->getParameter('post_id');

        //if post with post_id exists, update his value
        if ($post_id) {
            $post = $this->database->table('posts')->get($post_id);
            $post->update($values);
            $this->flashMessage('The post has been edited.', 'success');
        } //else create new post with post_id value
        else {
            $post = $this->database->table('posts')->insert($values);
            $this->flashMessage('New post has been published.', 'success');
        }


            //log into the streamhandler stream
            $log->info("New post created", $values);


    }

    public function actionEditPost($post_id)
    {
        //used to edit posts with {action} macro
        $post = $this->database->table('posts')->get($post_id);
        if(!$post_id){
            $this->error("Post not found");

    }
        //content is saved into the existing post_content
        $this['postForm']->setDefaults($post->toArray());
    }
    public function actionDeletePost($post_id)
    {
        //used to delete posts with {action} macro
        $post = $this->database->table('posts')->where('id',$post_id)->delete();
        if(!$post_id){
            $this->error("Post not found");

        }
        $this->flashMessage("Post has been deleted");

    }

}