<?php
use \Elastica\Client;
use \Elastica\Document;
use \Elastica\Exception\ResponseException;

class Model implements JsonSerializable {

  protected $_data = array();
  protected $_errors = null;
  protected $_type = null;

  public function __construct($data = null) {
    $this->hydrate(array('_model' => get_called_class()));
    if ($data) {
      $this->hydrate($data);
    }
  }

  public static function create($data) {
    $modelName = get_called_class();
    return new $modelName($data);
  }

  public static function createMany($data) {
    $modelName = get_called_class();
    return array_map(function($data) use ($modelName) {
      return $modelName::create($data);
    }, $data);
  }

  public static function find($options = array()) {
    $options = array_merge(array(
      'default_operator' => 'AND',
      'offset' => 0,
      'limit' => 25,
    ), (array)$options);

    $elasticaText = new \Elastica\Query\Term();
    $elasticaText->setTerm('_model', get_called_class());
    $elasticaBool = new \Elastica\Query\BoolQuery();
    $elasticaBool->addShould($elasticaText);

    $elasticaQuery = new \Elastica\Query($elasticaBool);
    $elasticaQuery->setFrom($options['offset']);
    $elasticaQuery->setLimit($options['limit']);
    $elasticaQuery->setSort(array(array('id' => array('order' => 'desc'))));

    $client = new Client(array('url' => ELASTICSEARCH_URL));
    $elasticaIndex = static::createIndex($client, 'artifacts');
    $elasticaResultSet = $elasticaIndex->search($elasticaQuery);
    $elasticaResults  = $elasticaResultSet->getResults();
    $totalResults = $elasticaResultSet->getTotalHits();

    $results = array();
    foreach ($elasticaResults as $elasticaResult) {
      $data = $elasticaResult->getData();
      $data['_id'] = $elasticaResult->getId();
      $results[] = static::create($data);
    }

    return $results;
  }

  public function toArray() {
    return $this->_data;
  }

  public function toJson() {
    return json_encode($this, JSON_PRETTY_PRINT);
  }

  public function jsonSerialize() {
    return $this->toArray();
  }

  public function errors() {
    return $this->_errors;
  }

  public function data($field, $default = null) {
    if (!array_key_exists($field, $this->_data)) {
      return $default;
    }
    return $this->_data[$field];
  }

  public function type() {
    if ($this->_type) {
      return $this->_type;
    }
    return strtolower(get_class($this));
  }

  public function attach($object) {
    $field = $object->type() . '_id';
    $this->_data[$field] = $object->data('_id');
  }

  public function hydrate($data) {
    foreach ($this->_fields as $field) {
      if (array_key_exists($field, $data)) {
        $this->_data[$field] = $data[$field];
      }
    }
  }

  public function save() {
    $client = new Client(array('url' => ELASTICSEARCH_URL));
    $elasticaIndex = static::_createIndex($client, 'artifacts');
    $type = $index->getType($this->type());

    $data = $this->_data;
    unset($data['_id']);

    if ($this->data('_id')) {
      $document = new Document($this->data('_id'), $data, $this->type(), 'artifacts');
      $response = $type->updateDocument($document);
    } else {
      $document = new Document('', $data, $this->type(), 'artifacts');
      $response = $type->addDocument($document);
    }

    if (!$response->isOk()) {
      if ($this->data('_id')) {
        $document = new Document($this->data('_id'), $data, $this->type(), 'artifacts');
        $response = $type->updateDocument($document);
      } else {
        $document = new Document('', $data, $this->type(), 'artifacts');
        $response = $type->addDocument($document);
      }
    }

    if (!$response->isOk()) {
      $this->_errors = $response->getData();
      return false;
    }

    $result = $response->getData();

    $this->hydrate(array('_id' => $result['_id']));

    return $result;
  }

  protected static function createIndex($client, $indexName) {
    $client = new Client(array('url' => ELASTICSEARCH_URL));
    $index = $client->getIndex($indexName);
    try {
      $index->create();
    } catch (ResponseException $e) {
      if (strpos($e->getMessage(), 'IndexAlreadyExistsException') === false) {
        throw $e;
      }
    }

    return $index;
  }

}
