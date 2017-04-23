<?php

use Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTemplateNameParser;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Validation;

include 'vendor/autoload.php';

class FormDemo
{
    /**
     * main controller function
     */
    public function formAction()
    {
        $renderingEngine = $this->createRenderingEngine();
        $formFactory = $this->createFormFactory($renderingEngine);

        $data = [];
        $form = $this->buildForm($formFactory, $data);
        $form->handleRequest();

        if ($form->isSubmitted()) {
            echo 'submitted data:';
            var_dump($form->getData());
            //note: return value of $form->getData() is the same as $data
        }

        $this->renderForm($form, $renderingEngine);
    }

    /**
     * creates a rendering engine that is able to render the form with
     * PHP templates located in
     * vendor/symfony/framework-bundle/Resources/views/Form/*.html.php
     */
    private function createRenderingEngine()
    {
        $reflClass = new \ReflectionClass('Symfony\Bundle\FrameworkBundle\FrameworkBundle');
        $root = realpath(dirname($reflClass->getFileName()) . '/Resources/views');
        $rootTheme = realpath(__DIR__ . '/Resources');

        //this nameParser is intended only for testing purposes, should not be used!
        $templateNameParser = new StubTemplateNameParser($root, $rootTheme);

        $engine = new PhpEngine($templateNameParser, new FilesystemLoader(array()));
        $engine->addHelpers(array(
            new TranslatorHelper(new IdentityTranslator()),
        ));

        return $engine;
    }

    /**
     * creates the formFactory that is responsible to assemble the forms based on our
     * field definitions, @see buildForm
     */
    private function createFormFactory(EngineInterface $engine)
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new TemplatingExtension($engine, null, array(
                'FrameworkBundle:Form',
            )))
            ->getFormFactory();
    }

    /**
     * let's build our form and
     * in the same time, pass initial $data that we suppose to edit
     */
    private function buildForm(FormFactoryInterface $formFactory, &$data = [])
    {
        return $formFactory->createBuilder(FormType::class, $data, ['method' => 'GET'])
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('age', IntegerType::class)
            ->add('gender', ChoiceType::class, array(
                'choices' => array('Male' => 'm', 'Female' => 'f'),
            ))
            ->add('save',SubmitType::class)
            ->getForm();
    }

    /**
     * render the form with out PHP file based templating engine
     */
    private function renderForm(Form $form, EngineInterface $engine)
    {
        $formView = $form->createView();

        /** @var FormHelper $formHelper */
        $formHelper = $engine->get('form');

        echo $formHelper->start($formView, []); //start tag <form>
        echo $formHelper->form($formView, []); //all the fields according the our formTemplate
        echo $formHelper->end($formView, []); //end tag </form>
    }
}

(new FormDemo())->formAction();