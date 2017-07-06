<?php

use Nette\Application\UI\Form;
use Nette\Application\UI\Control;

class PostForm extends Control
{
    private $database;

    public function __construct(Nette\Database\Connection $database)
    {
        $this->database = $database;
    }

    public function create($ajax)
    {

        $form = new Form;
        if ($ajax){
                $form->getElementPrototype()->setAttribute('class', 'ajax');
    }
        $form->addText('title', 'Title:')
            ->setRequired();
        $form->addText( 'name', 'Name:');
        $form->addEmail('email', 'Email address: ')->setDefaultValue('@')
            ->setRequired();

        $form->addTextArea('content', 'Content:')
            ->setRequired();
        $form->addSubmit('send', 'Save & publish')->setAttribute('class', 'btn btn-default');


        return $form;
    }


}