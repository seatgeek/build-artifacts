<?php
class Payload extends Model {
  public $_fields  = array(
    '_id', '_model', 'id', 'number', 'status', 'started_at', 'finished_at', 'status_message',
    'commit', 'branch', 'message', 'compare_url',
    'committed_at', 'committer_name', 'committer_email', 'author_name', 'author_email',
    'repository_id', 'repository_name', 'repository_owner_name', 'repository_url',
  );

  public function hydrate($data) {
    if (isset($data['repository'])) {
      foreach ($data['repository'] as $key => $value) {
        $data[sprintf('repository_' . $key)] = $value;
      }
    }

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

  public function getDownloadUrl() {
    return sprintf(DOWNLOAD_URL_TEMPLATE, $this->data(TRAVIS_IDENTIFIER));
  }

  public function isStable() {
    if ($this->data('branch') != STABLE_BRANCH) {
      return false;
    }

    if (strpos($this->data('compare_url'), '...') === false) {
      return false;
    }

    return $this->data('type', 'push') == 'push';
  }

  public function isPullRequest() {
    if ($this->data('type', 'push') == 'pull_request') {
      return true;
    }

    return strpos($this->data('compare_url'), '...') === false;
  }

  public function getRepoSlug() {
    return $this->data('repository_owner_name') . '/' . $this->data('repository_name');
  }

  public function getShortCommit() {
    return substr($this->data('commit'), 0, 7);
  }

  public function getCommittedAt() {
    return $this->data('committed_at')->format('D, M d Y, H:i');
  }
}
