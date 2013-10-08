<?php
class Matrix extends Model {
  public $_fields  = array(
    '_id', '_model', 'payload_id', 'id', 'repository_id', 'number', 'state', 'started_at', 'finished_at',
    'config', 'status', 'log', 'result', 'parent_id', 'commit', 'branch', 'status',
    'committed_at', 'committer_name', 'committer_email', 'author_name', 'author_email', 'compare_url',
  );

  public function hydrate($data) {
    foreach (array('started_at', 'committed_at', 'finished_at') as $key) {
      if (array_key_exists($key, $data) && $data[$key] !== null) {
        if (is_array($data[$key])) {
          $data[$key] = str_replace(' ', 'T', $data[$key]['date']) . $data[$key]['timezone'];
        }
        $data[$key] = new DateTime($data[$key]);
      }
    }

    parent::hydrate($data);
  }
}
