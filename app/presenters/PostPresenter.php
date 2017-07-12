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

    /** @var \PostForm @inject */
    public $postFormFactory;

    public function __construct(Nette\Database\Context $database)
    {
        //Nette database connection using DI
        $this->database= $database;


    }

    public function afterRender()
    {

        //ajaxing flash messages
        if ($this->isAjax() && $this->hasFlashSession())
            $this->redrawControl('flashes');
    }

    public function renderShowSingle($post_id)
    {
        //saves single post with post_id from the database into $post
        $post=$this->database->table('posts')->get($post_id);

        //if post with post_id doesn't exist, throw an error
        if (!$post) {
            $this->error('Page not found');
        }
        //renders single post and related comments (1:n post - comment)
        $this->template->post = $post;
        $this->template->comments = $post->related('comment')->order('timestamp');
    }


    protected function createComponentCommentForm()
    {
        //comment form factory
        $form = new Form; // Nette\Application\UI\Form
        $form->getElementPrototype()->setAttribute('class', 'ajax');
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


        //called upon successful posting of a comment
        $post_id = $this->getParameter('post_id');
        $this->database->table('comments')->insert([
            'post_id' => $post_id,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content,
        ]);
        if ($this->isAjax()){
            $this->redrawControl('comments');
        }
        else {
            $this->redirect('this');
        }

        $this->flashMessage('Thank you for your comment', 'success');

    }
    protected function createComponentPostForm()
    {
        $form = $this->postFormFactory->create(0);
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
        $post = $this->database->table('comments')->where('post_id',$post_id)->delete();
        $post = $this->database->table('posts')->where('id',$post_id)->delete();

        //if post with post_id doesn't exist, throw an error
        if(!$post_id){
            $this->error("Post not found");

        }
        $this->flashMessage("Post has been deleted");

    }

}