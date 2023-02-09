<?php

namespace App\Controller\Admin;
use App\Entity\User;
use App\Entity\Group;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordEncoder;
    private ManagerRegistry $doctrine;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, ManagerRegistry $doctrine)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->doctrine = $doctrine;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // find all groups
        // Unable to transform value for property path "groups_": Expected an array.
        $groups = $this->doctrine->getRepository(Group::class)->findAll();
        return [
            Field::new ('id')->hideOnForm(),
            Field::new ('name'),
            // roles between ROLE_USER and ROLE_ADMIN
            ChoiceField::new ('roles')
                ->allowMultipleChoices()
                ->setChoices([
                    'ROLE_USER' => 'ROLE_USER',
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                ]),
                //if password is empty, it will be ignored
            Field::new ('password', 'New password')->onlyWhenCreating()
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'New password'],
                    'second_options' => ['label' => 'Repeat password'],
                    'error_bubbling' => true,
                    'invalid_message' => 'The password fields do not match.',
                ]),
            Field::new('password', 'Password')->onlyWhenUpdating()
                ->setRequired(false)
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'New password'],
                    'second_options' => ['label' => 'Repeat password'],
                    'error_bubbling' => true,
                    'invalid_message' => 'The password fields do not match.',
                    'mapped' => false,
                ]),
                
        ];
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $plainPassword = $entityDto->getInstance()->getPassword();
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->addEncodePasswordEventListener($formBuilder, $plainPassword);

        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    protected function addEncodePasswordEventListener(FormBuilderInterface $formBuilder, $plainPassword = null): void
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($plainPassword) {
            /** @var User $user */
            $user = $event->getData();
            if ($user->getPassword() !== $plainPassword) {
                $user->setPassword($this->passwordEncoder->hashPassword($user, $user->getPassword()));
            }
        });
    }

}