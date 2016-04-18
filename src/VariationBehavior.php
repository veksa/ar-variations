<?
namespace Veksa\Variation;

use Yii;
use yii\base\Model;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\base\UnknownPropertyException;
use yii\base\InvalidConfigException;

class VariationBehavior extends Behavior
{
    /**
     * @var string name of relation
     */
    public $relation;

    /**
     * @var array|string of related fields.
     */
    public $related;

    public $relations;

    /**
     * @var \yii\db\ActiveQueryInterface[]|null list of all possible variation models.
     */
    private $_variationModels;

    /**
     * @var string name of relation, which corresponds default variation.
     */
    public $defaultRelation;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->related === null) {
            throw new InvalidConfigException('The "related" property must be set.');
        }
    }

    public function getDefaultModel()
    {
        return $this->findDefaultVariationModel();
    }

    /**
     * @return BaseActiveRecord|null
     */
    private function findDefaultVariationModel()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        if ($this->defaultRelation !== null) {
            if ($owner->isRelationPopulated($this->defaultRelation) || !$owner->isRelationPopulated($this->relation)) {
                return $this->owner->{$this->defaultRelation};
            } else {
                foreach ($this->owner->{$this->relation} as $model) {
                    $findDefault = true;
                    foreach ($this->related as $option => $related) {
                        if ($model->{$option} != $related['value']) {
                            $findDefault = false;
                        }
                    }

                    if ($findDefault) {
                        $owner->populateRelation($this->defaultRelation, $model);

                        return $model;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $exception) {
            $model = $this->getDefaultModel();
            if (is_object($model) && $model->hasAttribute($name)) {
                return $model->$name;
            }

            throw $exception;
        }
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if (parent::canGetProperty($name, $checkVars)) {
            return true;
        }

        if ($this->owner == null) {
            return false;
        }

        $model = $this->getDefaultModel();

        return is_object($model) && $model->hasAttribute($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $exception) {
            if ($this->owner !== null) {
                $model = $this->getDefaultModel();
                if ($model->hasAttribute($name)) {
                    $model->$name = $value;
                    return;
                }
            }

            throw $exception;
        }
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if (parent::canSetProperty($name, $checkVars)) {
            return true;
        }

        if ($this->owner == null) {
            return false;
        }

        $model = $this->getDefaultModel();

        return is_object($model) && $model->hasAttribute($name);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave'
        ];
    }

    /**
     * Returns models related to the main one as variations.
     * This method adjusts set of related models creating missing variations.
     *
     * @return BaseActiveRecord[] list of variation models.
     */
    public function getVariationModels()
    {
        if (is_array($this->_variationModels)) {
            return $this->_variationModels;
        }

        $variationModels = $this->owner->{$this->relation};
        $variationModels = $this->adjustModels($variationModels);
        $this->_variationModels = $variationModels;

        return $variationModels;
    }

    /**
     * Adjusts given variation models to be adequate to the related records.
     *
     * @param BaseActiveRecord[] $initialModels set of initial variation models, found by relation
     *
     * @return BaseActiveRecord[] list of [[BaseActiveRecord]]
     */
    private function adjustModels(array $initialModels)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $relation = $owner->getRelation($this->relation);

        $relatedArray = $this->related;
        foreach ($relatedArray as $key => $related) {
            /** @var ActiveRecord $relatedModelClass */
            $relatedModelClass = $related['class'];
            $query = $relatedModelClass::find();
            if (isset($related['queryFilter'])) {
                if (is_callable($related['queryFilter'])) {
                    call_user_func($related['queryFilter'], $query);
                } else {
                    $query->andWhere($related['queryFilter']);
                }
            }
            $relatedArray[$key]['models'] = $query->all();
        }

        $this->adjustModel($relation, $initialModels, array_values($relatedArray));

        return $initialModels;
    }

    /**
     * Adjusts given variation model to be adequate to the related records.
     *
     * @param ActiveQuery $relation relation of model
     * @param BaseActiveRecord[] $initialModels set of initial variation models, found by relation
     * @param array $relatedArray array of initial related models
     * @param array $relatedIds array of ids initial related models
     * @param int $inx index of stack
     */
    private function adjustModel($relation, array &$initialModels, array $relatedArray, $relatedIds = [], $inx = 0)
    {
        if ($inx >= count($relatedArray)) {
            return;
        }

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $option = $relatedArray[$inx]['option'];
        foreach ($relatedArray[$inx]['models'] as $model) {
            /** @var ActiveRecord $model */

            $relatedIds[$option] = [
                'option' => $option,
                'value' => $model->getPrimaryKey()
            ];
            if (count($relatedIds) == count($relatedArray)) {
                if (!$this->checkModels($initialModels, array_values($relatedIds))) {
                    list($ownerReferenceAttribute) = array_keys($relation->link);
                    $className = $relation->modelClass;
                    $newModel = new $className;
                    foreach ($relatedIds as $item) {
                        $newModel->{$item['option']} = $item['value'];
                    }
                    $newModel->$ownerReferenceAttribute = $owner->getPrimaryKey();

                    $initialModels[] = $newModel;
                }
            } else {
                $this->adjustModel($relation, $initialModels, $relatedArray, $relatedIds, ++$inx);
            }
        }
    }

    /**
     * Find model related to the array of [[related]]
     *
     * @param BaseActiveRecord[] $models array of models
     * @param array $related array of relations
     *
     * @return bool
     */
    private function checkModels(array $models, $related)
    {
        foreach ($models as $model) {
            if ($this->checkModel($model, $related)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check related model to [[related]]
     *
     * @param BaseActiveRecord $model
     * @param array $related array of relations
     * @param int $inx index of stack
     *
     * @return bool
     */
    private function checkModel($model, $related, $inx = 0)
    {
        if ($model->{$related[$inx]['option']} == $related[$inx]['value']) {
            $inx++;
            if ($inx < count($related)) {
                return $this->checkModel($model, $related, $inx);
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Declares has-one relation [[defaultRelation]] from [[relation]] relation.
     *
     * @return \yii\db\ActiveQueryInterface the relational query object.
     */
    public function hasDefaultVariationRelation()
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        /** @var ActiveQuery $relation */
        $relation = $owner->getRelation($this->relation);
        $relation->multiple = false;

        $condition = [];
        foreach ($this->related as $related) {
            $condition[$related['option']] = $related['value'];
        }

        $relation->andWhere($condition);

        return $relation;
    }

    /**
     * @return boolean whether the variation models have been initialized or not.
     */
    public function getIsVariationModelsInitialized()
    {
        return !empty($this->_variationModels);
    }

    public function beforeValidate()
    {
        Model::loadMultiple($this->getVariationModels(), $this->relations, '');
    }

    /**
     * Handles owner 'afterValidate' event, ensuring variation models are validated as well
     * in case they have been fetched.
     *
     * @param \yii\base\Event $event event instance.
     */
    public function afterValidate($event)
    {
        if (!$this->getIsVariationModelsInitialized()) {
            return;
        }

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $models = $this->getVariationModels();
        foreach ($models as $model) {
            if (!$model->validate()) {
                $owner->addErrors($model->getErrors());
            }
        }
    }

    /**
     * Handles owner 'afterInsert' and 'afterUpdate' events, ensuring variation models are saved
     * in case they have been fetched before.
     *
     * @param \yii\base\Event $event event instance.
     */
    public function afterSave($event)
    {
        if (!$this->getIsVariationModelsInitialized()) {
            return;
        }

        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        $relation = $owner->getRelation($this->relation);
        list($ownerReferenceAttribute) = array_keys($relation->link);
        $variationModels = $this->getVariationModels();

        foreach ($variationModels as $variationModel) {
            $variationModel->{$ownerReferenceAttribute} = $owner->getPrimaryKey();
            $variationModel->save(false);
        }
    }
}