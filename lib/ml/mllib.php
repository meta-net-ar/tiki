<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * MachineLearningLib
 *
 * @uses TikiDb_Bridge
 */
class MachineLearningLib extends TikiDb_Bridge
{
    private $table;

    /**
     *
     */
    public function __construct()
    {
        $this->table = $this->table('tiki_machine_learning_models');
    }

    public function get_models()
    {
        $models = $this->table->fetchAll();
        return array_map([$this, 'deserialize'], $models);
    }

    public function get_model($mlmId)
    {
        $model = $this->table->fetchFullRow(['mlmId' => $mlmId]);
        if (! $model) {
            return false;
        }
        $model = $this->deserialize($model);
        $model['instances'] = $this->hydrate($model['payload']);
        return $model;
    }

    public function set_model($mlmId, $data)
    {
        $data = $this->serialize($data);
        return $this->table->insertOrUpdate($data, ['mlmId' => $mlmId]);
    }

    public function delete_model($mlmId)
    {
        $this->table->delete(['mlmId' => $mlmId]);
        TikiLib::lib('cache')->invalidate($mlmId, 'mlmodel');
        return true;
    }

    public function hydrate($payload)
    {
        $instances = [];
        $payload = json_decode($payload);
        foreach ($payload as $learner) {
            $instances[] = $this->hydrate_single($learner->class, $learner->args);
        }
        return $instances;
    }

    public function hydrate_single($class, $args)
    {
        if (empty($class)) {
            return [
            'learner' => null,
            'class' => null,
            'instance' => null,
            'serialized_args' => null
            ];
        }
        $ref = new ReflectionClass('Rubix\ML\\' . $class);
        $instance_args = [];
        if ($args) {
            foreach ($args as $arg) {
                if ($arg->input_type == 'layers' && ! empty($arg->value)) {
                    $layers = [];
                    foreach ($arg->value as $argv) {
                        $instance = $this->hydrate_single($argv->class, $argv->args);
                        $layers[] = $instance['instance'];
                    }
                    $instance_args[] = $layers;
                } elseif ($arg->input_type == 'rubix' && ! empty($arg->value)) {
                    $iargs = $arg->value;
                    $instance = $this->hydrate_single($iargs->class, $iargs->args);
                    $instance_args[] = $instance['instance'];
                } else {
                    $instance_args[] = $arg->value;
                }
            }
        }
        try {
            $instance = $ref->newInstanceArgs($instance_args);
        } catch (TypeError $e) {
            Feedback::error(tr('Error instantiating %0 with arguments %1: %2', $class, print_r($instance_args, 1), $e->getMessage()));
            $instance = tr('(error instantiating)');
        }
        return [
        'learner' => preg_replace('/^[^\\\\]*\\\\/', '', $class),
        'class' => $class,
        'instance' => $instance,
        'serialized_args' => json_encode(['class' => $class, 'args' => $args])
        ];
    }

    public function train($model, $test = false)
    {
        $learner = null;
        $transformers = [];
        foreach ($model['instances'] as $row) {
            $instance = $row['instance'];
            if ($instance instanceof Rubix\ML\Transformers\Transformer) {
                $transformers[] = $instance;
            } elseif ($instance instanceof Rubix\ML\Learner) {
                $learner = $instance;
            } else {
                throw new Exception(tr('Not implemented: %0', get_class($instance)));
            }
        }
        $estimator = new Rubix\ML\Pipeline($transformers, $learner);

        $samples = [];
        $labels = [];

        $trklib = TikiLib::lib('trk');
        $items = $trklib->list_items($model['sourceTrackerId'], 0, $test ? 10 : -1);

        foreach ($items['data'] as $item) {
            $item = Tracker_Item::fromId($item['itemId']);
            switch ($model['labelField']) {
                case "itemId":
                    $label = intval($item->getId());
                    break;
                case "itemTitle":
                    $label = $trklib->get_isMain_value($model['sourceTrackerId'], $item->getId());
                    $definition = Tracker_Definition::get($model['sourceTrackerId']);
                    $mainFieldId = $definition->getMainFieldId();
                    $field = $definition->getFieldInfoFromFieldId($mainFieldId);
                    $mainFieldType = $field['type'];
                    if ($mainFieldType === "n") {
                        $label = floatval($label);
                    }
                    break;
                default:
                        $label = $trklib->get_item_value($model['sourceTrackerId'], $item->getId(), $model['labelField']);
                    if ($trklib->get_tracker_field($model['labelField'], false)['type'] === "n") {
                        $label = floatval($label);
                    }
            }
                $sample = [];
            foreach ($model['trackerFields'] as $fieldId) {
                $field = $trklib->get_tracker_field($fieldId, false);
                $value = $trklib->get_item_value($model['sourceTrackerId'], $item->getId(), $fieldId);

                if (empty($value) && $model['ignoreEmpty']) {
                    continue 2;
                }

                if ($field['type'] === "n") {
                    $value = floatval($value);
                }

                $sample[] = $value;
            }
            if (empty($sample)) {
                continue;
            }
                $samples[] = $sample;
                $labels[] = $label;
        }

        if (empty($samples) || empty($labels)) {
            throw new Exception(tr("No data found in data source. Check your model settings."));
        }

        if ($model['labelField']) {
            $dataset = Rubix\ML\Datasets\Labeled::build($samples, $labels);
        } else {
            $dataset = Rubix\ML\Datasets\Unlabeled::build($samples);
        }
        $estimator->train($dataset);

        if (! $test) {
            $serialized = serialize($estimator);
            TikiLib::lib('cache')->cacheItem($model['mlmId'], $serialized, 'mlmodel');
            if ($serialized != TikiLib::lib('cache')->getCached($model['mlmId'], 'mlmodel')) {
                throw new Exception(tr('Model trained but could not be saved to cache. Check cache storage permissions.'));
            }
        }
    }

    public function probaSample($model, $processedFields)
    {
        $sample = [];
        foreach ($processedFields as $field) {
            $sample[] = $field['type'] === "n" ? floatval($field['value']) : $field['value'];
        }

        $estimator = $this->getTrainedModel($model);
        if ($estimator instanceof Rubix\ML\Pipeline) {
            $realEstimator = $estimator->base();
        } else {
            $realEstimator = $estimator;
        }
        if (! method_exists($realEstimator, 'probaSample')) {
            return [];
        }

        if ($model['labelField']) {
            $dataset = Rubix\ML\Datasets\Labeled::build([$sample], ['Sample']);
        } else {
            $dataset = Rubix\ML\Datasets\Unlabeled::build([$sample]);
        }

        if ($estimator instanceof Rubix\ML\Pipeline) {
            $result = $estimator->proba($dataset);
            $result = array_shift($result);
        } else {
            $result = $estimator->probaSample($sample);
        }

        $result = array_filter($result);
        arsort($result);

        return $result;
    }

    public function predictSample($model, $processedFields)
    {
        $sample = [];
        foreach ($processedFields as $field) {
            $sample[] = $field['type'] === "n" ? floatval($field['value']) : $field['value'];
        }

        $estimator = $this->getTrainedModel($model);

        $result = $estimator->predict(Rubix\ML\Datasets\Unlabeled::build([$sample]));

        return $result[0] ?? "";
    }

    public function isRegressor($model)
    {
        $estimator = $this->getTrainedModel($model);
        return $estimator->type() == Rubix\ML\EstimatorType::regressor();
    }

    public function ensureModelTrained($model)
    {
        $this->getTrainedModel($model);
    }

    public function predefined($template)
    {
        switch ($template) {
            case 'MLT':
                return json_encode([
                    [
                        "class" => "Transformers\\TextNormalizer",
                        "args" => []
                    ], [
                        "class" => "Transformers\\StopWordFilter",
                        "args" => [
                            [
                                "name" => "stopWords",
                                "default" => [],
                                "arg_type" => "array",
                                "input_type" => "text",
                                "value" => ["i","me","my","myself","we","our","ours","ourselves","you","your","yours","yourself","yourselves","he","him","his","himself","she","her","hers","herself","it","its","itself","they","them","their","theirs","themselves","what","which","who","whom","this","that","these","those","am","is","are","was","were","be","been","being","have","has","had","having","do","does","did","doing","a","an","the","and","but","if","or","because","as","until","while","of","at","by","for","with","about","against","between","into","through","during","before","after","above","below","to","from","up","down","in","out","on","off","over","under","again","further","then","once","here","there","when","where","why","how","all","any","both","each","few","more","most","other","some","such","no","nor","not","only","own","same","so","than","too","very","s","t","can","will","just","don","should","now"]
                            ]
                        ]
                    ], [
                        "class" => "Transformers\\WordCountVectorizer",
                        "args" => [
                            [
                                "name" => "maxVocabularySize",
                                "default" => PHP_INT_MAX,
                                "arg_type" => "int",
                                "input_type" => "text",
                                "value" => 10000
                            ], [
                                "name" => "minDocumentCount",
                                "default" => 1,
                                "arg_type" => "float",
                                "input_type" => "text",
                                "value" => "1"
                            ], [
                                "name" => "maxDocumentRatio",
                                "default" => 0.8,
                                "arg_type" => "float",
                                "input_type" => "text",
                                "value" => "0.8"
                            ], [
                                "name" => "tokenizer",
                                "default" => [
                                    "class" => "Tokenizers\\Word",
                                    "args"  => [],
                                ],
                                "arg_type" => "Rubix\\ML\\Tokenizers\\Tokenizer",
                                "input_type" => "rubix",
                                "value" => [
                                    "class" => "Tokenizers\\Word",
                                    "args"  => [],
                                ],
                            ]
                        ]
                    ], [
                        "class" => "Transformers\\BM25Transformer",
                        "args" => [
                            [
                                "name" => "alpha",
                                "default" => 1.2,
                                "arg_type" => "float",
                                "input_type" => "text",
                                "value" => 1.2
                            ], [
                                "name" => "beta",
                                "default" => 0.75,
                                "arg_type" => "float",
                                "input_type" => "text",
                                "value" => 0.75
                            ]
                        ]
                    ], [
                        "class" => "Classifiers\\KDNeighbors",
                        "args" => [
                            [
                                "name" => "k",
                                "default" => 5,
                                "arg_type" => "int",
                                "input_type" => "text",
                                "value" => 20
                            ], [
                                "name" => "weighted",
                                "default" => true,
                                "arg_type" => "bool",
                                "input_type" => "text",
                                "value" => "true"
                            ], [
                                "name" => "tree",
                                "default" => [
                                    "class" => "Graph\\Trees\\KDTree",
                                    "args"  => [],
                                ],
                                "arg_type" => "Rubix\\ML\\Graph\\Trees\\Spatial",
                                "input_type" => "rubix",
                                "value" => [
                                    "class" => "Graph\\Trees\\BallTree",
                                    "args" => [
                                        [
                                            "name" => "maxLeafSize",
                                            "default" => 30,
                                            "arg_type" => "int",
                                            "input_type" => "text",
                                            "value" => 20
                                        ], [
                                            "name" => "kernel",
                                            "default" => [
                                                "class" => "Kernels\\Distance\\Euclidean",
                                                "args"  => [],
                                            ],
                                            "arg_type" => "Rubix\\ML\\Kernels\\Distance\\Distance",
                                            "input_type" => "rubix",
                                            "value" => [
                                                "class" => "Kernels\\Distance\\Cosine",
                                                "args" => []
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
            default:
                return '';
        }
    }

    protected function getTrainedModel($model)
    {
        $estimator = TikiLib::lib('cache')->getSerialized($model['mlmId'], 'mlmodel');
        if (! $estimator || ! $estimator->trained()) {
            throw new Exception(tr('Model was not trained.'));
        }
        return $estimator;
    }

    protected function serialize($model)
    {
        if (is_array($model['trackerFields'])) {
            $model['trackerFields'] = implode(',', $model['trackerFields']);
        }
        return $model;
    }

    protected function deserialize($model)
    {
        $model['trackerFields'] = array_filter(explode(',', $model['trackerFields']));
        if (empty($model['payload'])) {
            $model['payload'] = '[]';
        }
        return $model;
    }
}
