<?php
/**
* Фильтр, убирающий стремный символ в начале передачи JSON от УТК
* Называется так потому, что Василий 1Сник никак не хочет его убрать
*/

class DeVasyaStreamFilter extends php_user_filter {
	protected $stripped = false;

	public function filter($in, $out, &$consumed, $closing) {

    while ($bucket = stream_bucket_make_writeable($in)) {
      if (!$this->stripped) {
        $bucket->data = preg_replace('/^\S*\{/', '{', $bucket->data);
      }

      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }

		return PSFS_PASS_ON;
	}
}
