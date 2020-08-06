<?php

namespace Drupal\ucsc_profiles;

class ProfilesService {
  private $cache;
  
  public function __construct() {
    $this->cache = \Drupal::service('keyvalue.expirable')->get('ucsc_profiles'); 
  }
  
  private function genResponseIndexMap($profiles) {
  	$result = array();
  	foreach($profiles as $num => $entry) {
  		$result[$entry['uid'][0]] = $num;
  	}
  	return $result;
  }

  private function loadAttrs($attributes) {
    $attrsForQuery = array();
    $uids = preg_split('/[\s,]+/', $attributes['uids']);
    if($attributes['jpegPhoto']) {
	  	array_push($attrsForQuery, 'jpegPhoto');
	  }
	  if($attributes['cn']) {
	  	array_push($attrsForQuery, 'cn');
	  }
	  if($attributes['title']) {
	  	array_push($attrsForQuery, 'title');
	  }
	  if($attributes['telephoneNumber']) {
	  	array_push($attrsForQuery, 'telephoneNumber');
	  }
	  if($attributes['mail']) {
	  	array_push($attrsForQuery, 'mail');
	  }
	  if($attributes['labeledURI']) {
	  	array_push($attrsForQuery, 'labeledURI');
	  }
	  if($attributes['ucscPersonPubOfficeLocationDetail']) {
	  	array_push($attrsForQuery, 'ucscPrimaryLocationPubOfficialName');
	  	array_push($attrsForQuery, 'ucscPersonPubOfficeLocationDetail');
	  }
	  if($attributes['ucscPersonPubOfficeHours']) {
	  	array_push($attrsForQuery, 'ucscPersonPubOfficeHours');
	  }
	  if($attributes['ucscPersonPubAreaOfExpertise']) {
	  	array_push($attrsForQuery, 'ucscPersonPubAreaOfExpertise');
	  }
	  if($attributes['ucscPersonPubDescription']) {
	  	array_push($attrsForQuery, 'ucscPersonPubDescription');
	  }
	  if($attributes['ucscPersonPubExpertiseReference']) {
	  	array_push($attrsForQuery, 'ucscPersonPubExpertiseReference');
	  }
	  if($attributes['ucscPersonPubResearchInterest']) {
	  	array_push($attrsForQuery, 'ucscPersonPubResearchInterest');
	  }
	  if($attributes['ucscPersonPubTeachingInterest']) {
	  	array_push($attrsForQuery, 'ucscPersonPubTeachingInterest');
	  }
	  if($attributes['ucscPersonPubAwardsHonorsGrants']) {
	  	array_push($attrsForQuery, 'ucscPersonPubAwardsHonorsGrants');
	  }
	  if($attributes['ucscPersonPubSelectedPublication']) {
	  	array_push($attrsForQuery, 'ucscPersonPubSelectedPublication');
	  }
    return $attrsForQuery;
  }

  private function renderAttrCn($values, $val_key, $options, $attributes, $uid) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		if($attributes['profLinks']) {
  			$result .= '<a style="text-decoration: none" href="' . $options['profile_server_url'] . $uid . '">';
  			$result .= $values[$val_key][0] . '</a>';
  		} else {
  			$values[$val_key][0];
  		}
  	}
  	return $result;
  }
  private function ucscCdpReadMore($data, $options, $uid) {
  	$original = strip_tags($data);
  	$original_length = strlen($original);
  	if($original_length < 128) {
  		return wp_kses_post($data);
  	}
  	$result = '<p>' . substr(strip_tags($data), 0, 128);
  	$result .= ' <a href="' . $options['profile_server_url'] . $uid . '">...more</a></p>';
  	return $result;
  }
  private function renderAttrSingleLine($values, $val_key) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		$result .= $values[$val_key][0];
  	}
  	return $result;
  }
  private function renderAttrMultiLine($values, $val_key) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		$result .= '<div>' . join('<br />', $values[$val_key]) . '</div>';
  	}
  	return $result;
  }
  private function renderAttrLabeledUri($values, $val_key) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		$result .= '<div>' . join('<br />', array_map(array($this, 'renderAttrLabeledUriMap'), $values[$val_key])) . '</div>';
  	}
  	return $result;
  }
  private function renderAttrMail($values, $val_key) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		$result .= '<div>' . join('<br />', array_map(array($this, 'renderAttrMailMap'), $values[$val_key])) . '</div>';
  	}
  	return $result;
  }
  private function renderAttrPhoto($values, $val_key) {
  	$result = '';
  	if(!empty($values[$val_key])) {
  		$result .= '<div class="square-img" style="background-image: url(\'data:image/jpeg;base64, ' . $values[$val_key][0] . '\')"></div>';
  	} else {
  		$result .= '<div class="square-img" style="background-color: gray"></div>';
  	}
  	return $result;
  }
  private function renderListAttr($title, $content) {
  	$result = '<li><span class="cdp-li-header">' . $title . '</span><ul class="cdp-inline-list">' . $content . '</ul></li>';
  	return $result;
  }
  private function renderGridAttr($content) {
  	$result = '<li>' . $content . '</li>';
  	return $result;
  }
  private function renderAttrMailMap($email) {
  	return '<a style="text-decoration:none" href="mailto:' . $email . '">' . $email . '</a>';
  }
  private function renderAttrLabeledUriMap($labeled_uri) {
  	$split = explode(' ', $labeled_uri, 2);
  	if(sizeof($split) < 2) {
  		return join('<br/>', $labeled_uri);
  	}
  	return '<a href="' . $split[0] . '">' . $split[1] . '</a>';
  }

  private function sendCdpRequest($filter, $attributeNames, $uri, $key) {
    if(!in_array('uid', $attributeNames)) {
	  	array_push($attributeNames, 'uid');
	  }
    $client = \Drupal::httpClient();
    $options = [
      'headers' => [
        'x-api-key' => $key,
      ],
      'json' => [
        'filter' => $filter,
        'attributeNames' => $attributeNames,
      ],
    ];
    return $client->post($uri, $options);
  }

  private function marshalOrFilterFromUids($uids) {
  	$result = '(|';
  	foreach($uids as $entry) {
  		$result .= '(uid=' . trim($entry) . ')';
  	}
  	$result .= ')';
  	return $result;
  }
  private function marshalQuerySignature($filter, $attrs) {
  	return "ucsc_profiles.query." . md5($filter . json_encode($attrs), false);
  }
  private function renderProfilesGrid($uids, $profiles, $attributes, $options) {
  	$indexMap = $this->genResponseIndexMap($profiles);
  	$result = '<div class="cdp-profiles cdp-display-' . $attributes['displayStyle'] . '">';
  	foreach($uids as $uid_value) {
  		$entry = null;
  		if(isset($profiles[$indexMap[$uid_value]])) {
  			$entry = $profiles[$indexMap[$uid_value]];
  		} else {
  			continue;
  		}
  		$profile_uid = $entry['uid'][0];
  		$result .= '<div class="cdp-profile grid" id="cdp-profile-';
  		$result .= $entry['uid'][0] . '"><ul class="cdp-profile-ul">';
  		if($attributes['jpegPhoto']) {
  			$result .= $this->renderAttrPhoto($entry, 'jpegPhoto', $options);
  		}
  		if($attributes['cn'] && !empty($entry['cn'])) {
  			$result .= $this->renderGridAttr('<strong>' . $this->renderAttrCn($entry, 'cn', $options, $attributes, $entry['uid'][0]) . '</strong>');
  		}
  		if($attributes['title'] && !empty($entry['title'])) {
  			$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'title', $options));
  		}
  		if($attributes['telephoneNumber'] && !empty($entry['telephoneNumber'])) {
  			$result .= $this->renderGridAttr($this->renderAttrMultiLine($entry, 'telephoneNumber', $options));
  		}
  		if($attributes['mail'] && !empty($entry['mail'])) {
  			$result .= $this->renderGridAttr($this->renderAttrMail($entry, 'mail', $options));
  		}
  		if($attributes['labeledURI'] && !empty($entry['labeledURI'])) {
  			$result .= $this->renderGridAttr($this->renderAttrLabeledUri($entry, 'labeledURI', $options));
  		}
  		if($attributes['ucscPersonPubOfficeLocationDetail'] && !empty($entry['ucscPersonPubOfficeLocationDetail'])) {
  			$office_info = $this->renderAttrMultiLine($entry, 'ucscPrimaryLocationPubOfficialName', $options);
  			$office_info .= $this->renderAttrMultiLine($entry, 'ucscPersonPubOfficeLocationDetail', $options);
  			$result .= $this->renderGridAttr($office_info);
  		}
  		if($attributes['ucscPersonPubOfficeHours'] && !empty($entry['ucscPersonPubOfficeHours'])) {
  			$result .= $this->renderGridAttr($this->renderAttrMultiLine($entry, 'ucscPersonPubOfficeHours', $options));
  		}
  		if($attributes['ucscPersonPubAreaOfExpertise'] && !empty($entry['ucscPersonPubAreaOfExpertise'])) {
  			if($attributes['ucscPersonPubAreaOfExpertise'] === 'short') {
  				$result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubAreaOfExpertise', $options, $attributes), $options, $profile_uid));
  			} else {
  				$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubAreaOfExpertise', $options, $attributes));
  			}
  		}
  		if($attributes['ucscPersonPubDescription'] && !empty($entry['ucscPersonPubDescription'])) {
  			if($attributes['ucscPersonPubDescription'] === 'short') {
  			        $result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubDescription', $options, $attributes), $options, $profile_uid));
  			} else {
  			        $result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubDescription', $options, $attributes));
  			}
  		}
  		if($attributes['ucscPersonPubExpertiseReference'] && !empty($entry['ucscPersonPubExpertiseReference'])) {
  			$result .= $this->renderGridAttr($this->renderAttrMultiLine($entry, 'ucscPersonPubExpertiseReference', $options, $attributes));
  		}
  		if($attributes['ucscPersonPubResearchInterest'] && !empty($entry['ucscPersonPubResearchInterest'])) {
  			if($attributes['ucscPersonPubResearchInterest'] === 'short') {
  				$result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubResearchInterest', $options, $attributes), $options, $profile_uid));
  			} else {
  				$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubResearchInterest', $options, $attributes));
  			}
  		}
  		if($attributes['ucscPersonPubTeachingInterest'] && !empty($entry['ucscPersonPubTeachingInterest'])) {
  			if($attributes['ucscPersonPubTeachingInterest'] === 'short') {
  				$result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubTeachingInterest', $options, $attributes), $options, $profile_uid));
  			} else {
  				$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubTeachingInterest', $options, $attributes));
  			}
  		}
  		if($attributes['ucscPersonPubAwardsHonorsGrants'] && !empty($entry['ucscPersonPubAwardsHonorsGrants'])) {
  			if($attributes['ucscPersonPubAwardsHonorsGrants'] === 'short') {
  				$result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubAwardsHonorsGrants', $options, $attributes), $options, $profile_uid));
  			} else {
  				$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubAwardsHonorsGrants', $options, $attributes));
  			}
  		}
  		if($attributes['ucscPersonPubSelectedPublication'] && !empty($entry['ucscPersonPubSelectedPublication'])) {
  			if($attributes['ucscPersonPubSelectedPublication'] === 'short') {
  				$result .= $this->renderGridAttr($this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubSelectedPublication', $options, $attributes), $options, $profile_uid));
  			} else {
  				$result .= $this->renderGridAttr($this->renderAttrSingleLine($entry, 'ucscPersonPubSelectedPublication', $options, $attributes));
  			}
  		}
  		$result .= '</ul></div>';
  	}
  	$result .= '</div>';
  	return $result;
  }

  private function renderProfilesList($uids, $profiles, $attributes, $options) {
  	$indexMap = $this->genResponseIndexMap($profiles);
  	$result = '<div class="cdp-profiles-list">';
  	foreach($uids as $uid_value) {
  		$entry = null;
  		if(isset($profiles[$indexMap[$uid_value]])) {
  			$entry = $profiles[$indexMap[$uid_value]];
  		} else {
  			continue;
  		}
  		$profile_uid = $entry['uid'][0];
  		$result .= '<div class="cdp-list-profile" id="cdp-profile-';
  		$result .= $profile_uid . '">';
  		if($attributes['cn'] && !empty($entry['cn'])) {
  			$result .= '<h4>' . $this->renderAttrCn($entry, 'cn', $options, $attributes, $profile_uid) . '</h4>';
  		}
  		$result .= '<div class="cdp-list-box"><div class="cdp-list-body"><ul class="cdp-list-render">';
  		if($attributes['title'] && !empty($entry['title'])) {
  			$result .= $this->renderListAttr('Title', '<li>' . $this->renderAttrSingleLine($entry, 'title', $options, $attributes) . '</li>');
  		}
  		if($attributes['telephoneNumber'] && !empty($entry['telephoneNumber'])) {
  			$result .= $this->renderListAttr('Phone', '<li>' . $this->renderAttrMultiLine($entry, 'telephoneNumber', $options, $attributes) . '</li>');
  		}
  		if($attributes['mail'] && !empty($entry['mail'])) {
  			$result .= $this->renderListAttr('Email', '<li>' . $this->renderAttrMail($entry, 'mail', $options, $attributes) . '</li>');
  		}
  		if($attributes['labeledURI'] && !empty($entry['labeledURI'])) {
  			$result .= $this->renderListAttr('Website', '<li>' . $this->renderAttrLabeledUri($entry, 'labeledURI', $options, $attributes). '</li>');
  		}
  		if($attributes['ucscPersonPubOfficeLocationDetail'] && !empty($entry['ucscPersonPubOfficeLocationDetail'])) {
  			$result .= $this->renderListAttr('Office Location', '<li>' . $this->renderAttrMultiLine($entry, 'ucscPrimaryLocationPubOfficialName', $options) . '</li><li>' . $this->renderAttrMultiLine($entry, 'ucscPersonPubOfficeLocationDetail', $options, $attributes) . '</li>');
  		}
  		if($attributes['ucscPersonPubOfficeHours'] && !empty($entry['ucscPersonPubOfficeHours'])) {
  			$result .= $this->renderListAttr('Office Hours', '<li>' . $this->renderAttrMultiLine($entry, 'ucscPersonPubOfficeHours', $options, $attributes) . '</li>');
  		}
  		if($attributes['ucscPersonPubAreaOfExpertise'] && !empty($entry['ucscPersonPubAreaOfExpertise'])) {
  			if($attributes['ucscPersonPubAreaOfExpertise'] === 'short') {
  				$result .= $this->renderListAttr('Summary of Expertise', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubAreaOfExpertise', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Summary of Expertise', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubAreaOfExpertise', $options, $attributes) . '</li>');
  			}
  		}
  		if($attributes['ucscPersonPubDescription'] && !empty($entry['ucscPersonPubDescription'])) {
  			if($attributes['ucscPersonPubDescription'] === 'short') {
  				$result .= $this->renderListAttr('Biography, Education, and Training', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubDescription', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Biography, Education, and Training', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubDescription', $options, $attributes) . '</li>');
  			}
  		}
  		if($attributes['ucscPersonPubExpertiseReference'] && !empty($entry['ucscPersonPubExpertiseReference'])) {
  			$result .= $this->renderListAttr('Areas of Expertise', '<li>' . $this->renderAttrMultiLine($entry, 'ucscPersonPubExpertiseReference', $options, $attributes) . '</li>');
  		}
  		if($attributes['ucscPersonPubResearchInterest'] && !empty($entry['ucscPersonPubResearchInterest'])) {
  			if($attributes['ucscPersonPubResearchInterest'] === 'short') {
  				$result .= $this->renderListAttr('Research Interests', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubResearchInterest', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Research Interests', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubResearchInterest', $options, $attributes) . '</li>');
  			}
  		}
  		if($attributes['ucscPersonPubTeachingInterest'] && !empty($entry['ucscPersonPubTeachingInterest'])) {
  			if($attributes['ucscPersonPubTeachingInterest'] === 'short') {
  				$result .= $this->renderListAttr('Teaching Interests', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubTeachingInterest', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Teaching Interests', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubTeachingInterest', $options, $attributes) . '</li>');
  			}
  		}
  		if($attributes['ucscPersonPubAwardsHonorsGrants'] && !empty($entry['ucscPersonPubAwardsHonorsGrants'])) {
  			if($attributes['ucscPersonPubAwardsHonorsGrants'] === 'short') {
  				$result .= $this->renderListAttr('Awards, Honors, and Grants', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubAwardsHonorsGrants', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Awards, Honors, and Grants', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubAwardsHonorsGrants', $options, $attributes) . '</li>');
  			}
  		}
  		if($attributes['ucscPersonPubSelectedPublication'] && !empty($entry['ucscPersonPubSelectedPublication'])) {
  			if($attributes['ucscPersonPubSelectedPublication'] === 'short') {
  				$result .= $this->renderListAttr('Selected Publications', '<li>' . $this->ucscCdpReadMore($this->renderAttrSingleLine($entry, 'ucscPersonPubSelectedPublication', $options, $attributes), $options, $profile_uid) . '</li>');
  			} else {
  				$result .= $this->renderListAttr('Selected Publications', '<li>' . $this->renderAttrSingleLine($entry, 'ucscPersonPubSelectedPublication', $options, $attributes) . '</li>');
  			}
  		}
  		$result .= '</ul></div>';
  		if($attributes['jpegPhoto']) {
  			$result .= $this->renderAttrPhoto($entry, 'jpegPhoto', $options, $attributes);
  		}
  		$result .= '</div></div>';
  	}
  	$result .= '</div>';
  	return $result;
  }

  public function render($attributes) {
    // Load settings and set attribute defaults.
    $attrsForQuery = $this->loadAttrs($attributes);
    $uids = preg_split('/[\s,]+/', $attributes['uids']);

    // Load configuration.
    $config = \Drupal::service('config.factory')->get('ucsc_profiles.settings');
    $profileServerUrl = $config->get('ucsc_profiles_server');

    // Marshal the searchRequest filter.
    $filter = $this->marshalOrFilterFromUids($uids);
    $querySignature = $this->marshalQuerySignature($filter, $attrsForQuery);

    // Load profiles data from cache or request new cache data.
    $cacheTTL = $config->get('ucsc_profiles_cache_ttl');
    $dirLink = $config->get('ucsc_profiles_directory_link');
    $key = $config->get('ucsc_profiles_key');
    if ($key === NULL || $profileServerUrl === NULL) {
      \Drupal::logger('ucsc_profiles')->error('unable to send request with empty key and or url');
      return '';
    }

    // Set profiles to the result of the query's signature.
    // $profiles = $this->cache->get($querySignature);
    $profiles = 1;
    // If profiles is not an array, it has expired and our cache must be replenished.
    if (!is_array($profiles)) {
      // Send a request to the proxy server for profile data.
      $response = $this->sendCdpRequest($filter, $attrsForQuery, $profileServerUrl, $key);
      $respCode = $response->getStatusCode();

      if ($respCode !== 200) {
        \Drupal::logger('ucsc_profiles')->error('request failed: got ' . $respCode . ' from proxy');
        
        // Retry once.
        $response = $client->post($profileServerUrl, $options);
        $respCode = $response->getStatusCode();
        if ($respCode !== 200) {
          \Drupal::logger('ucsc_profiles')->error('request failed on retry: got ' . $respCode . ' from proxy');
          return '';
        }
      }

      // A successful request has been made, and now we can replenish our cache.
      $this->cache->set($querySignature, json_decode($response->getBody(), true), $cacheTTL);
      $profiles = $this->cache->get($querySignature);
    } 

    $renderOpts = ['profile_server_url' => $dirLink];

  	$result = '';

    if (count($profiles) === 0) {
      return $result;
    }
    $result .= '<style>' . $this->style . '</style>';
  	if ($attributes['displayStyle'] === 'list') {
   		$result .= $this->renderProfilesList($uids, $profiles, $attributes, $renderOpts);
   	} else {
   		$result .= $this->renderProfilesGrid($uids, $profiles, $attributes, $renderOpts);
   	}
  	return $result;
  }

  private $style = '
ul.cdp-list-render {
	margin-left: 0px;
	margin-bottom: 0px;
}
.cdp-profile {
	display: flex;
	margin-right: 1em;
	font-size: .9em;
}
.cdp-profile.grid {
	display: flex;
	margin-right: 1em;
	font-size: .9em;
	max-width: 160px;
}
.cdp-profile.grid ul {
	max-width: 160px;
}
.cdp-list-profile {
	padding: .5em 1em;
        margin-bottom: 1em;
}
.cdp-list-box {
        display: flex;
	font-size: 0.9em;
}
.cdp-list-profile .cdp-list-body .cdp-list-render li {
	display: flex;
}
.cdp-inline-list {
	margin: 0px;
}
.cdp-inline-list li{
	margin-top: 0px;
}
span.cdp-li-header {
	flex-grow: 0;
	flex-shrink: 0;
	flex-basis: 140px;
	text-align: right;
	margin-right: 1em;
	font-weight: bold;
}
div.cdp-list-profile h4 {
	margin-top: 0px;
	margin-bottom: 10px;
	width: 480px;
}
.cdp-list-body {
	padding: 0;
	width: calc(100% - 160px);
	flex-grow: 1;
	margin-right: 1em;
}
.cdp-profiles {
	display: flex;
	flex-wrap: wrap;
}
.cdp-profile-ul {
	list-style-type: none;
	margin: 0;
	width: 400px;
	font-size: 1em; 
}
.cdp-profile-ul > li {
	margin: 0;
	border-bottom: 1px;
	border-style: none none solid none;
}
.cdp-profile-ul > li:last-child {
	margin: 0;
	border-style: none;
}
.cdp-profile-ul > li:first-child {
	margin: 0;
	border-style: none;
}
.cdp-profiles.alignfull {
	margin-left: 1.5em;
	margin-right: 1.5em;
}
.square-img {
	height: 160px;
	width: 160px;
	border-radius: 5px;
	background-size: cover;
}';
}
