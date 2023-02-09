<?php

namespace App\Controller\Admin;

use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class GroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Group::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new ('id')->hideOnForm(),
            Field::new ('name'),
            AssociationField::new ('user')
        ];
    }
    
    /*disable the "delete" action for this CRUD controller if there are users in group */
    
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->addCssClass('action-delete')->displayIf(function (Group $group) {
                    return $group->getUser()->isEmpty();
                });
            });
    }

}
