<?php

namespace Glpi\Controller\Rule;

use Glpi\Exception\Http\BadRequestHttpException;

trait RuleControllerTrait
{
    /**
     * @param class-string<\Rule> $item_subtype
     * @param int $entity
     * @return \RuleCollection
     * @throw BadRequestHttpException
     */
    private function getRuleCollectionInstanceFromRuleSubtype(string $item_subtype, int $entity): \RuleCollection
    {
        if (class_exists($item_subtype) === false) {
            throw new BadRequestHttpException(sprintf('Invalid rule subtype "%s"', htmlescape($item_subtype)));
        }
        $rule = new $item_subtype();
        $collection_classname = $rule->getCollectionClassName();

        /**
         * Not all classes extendending RuleCollection have a constructor.
         * Only \RuleCommonITILObjectCollection instances, so we can really pass an entity parameter to the constructor.
         */
        /* @phpstan-ignore-next-line */
        return new $collection_classname($entity);
    }
}