<?php

namespace Drupal\ucsc_profiles\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\filter\FilterProcessResult;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for displaying a profile section.
 *
 * @Shortcode(
 *   id = "ucsc_profiles",
 *   title = @Translation("Profiles"),
 *   description = @Translation("Displays a profile section")
 * )
 */
class ProfilesShortcode extends ShortcodeBase {
  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $sa = $this->getAttributes([
      'cruzids' => 'cosmo',
		  'photo' => true,
		  'name' => true,
		  'title' => false,
		  'phone' => false,
		  'email' => false,
		  'websites' => false,
		  'officelocation' => false,
		  'officehours' => false,
		  'expertise' => false,
		  'profilelinks' => true,
		  'biography' => false,
		  'areas_of_expertise' => false,
		  'research_interests' => false,
		  'teaching_interests' => false,
		  'awards' => false,
		  'publications' => false,
		  'displaystyle' => 'grid',
      ],
        $attributes
    );
    foreach($sa as $key => $value) {
		  if($key === 'cruzids' || $key === 'displaystyle') {
		  	continue;
		  }
		  if($value === 'true') {
		  	$sa[$key] = true;
		  }
		  if($value === 'false') {
		  	$sa[$key] = false;
		  }
    }
	  $attrs = array(
	  	'uids' => $sa['cruzids'],
	  	'jpegPhoto' => $sa['photo'],
	  	'cn' => $sa['name'],
	  	'title' => $sa['title'],
	  	'telephoneNumber' => $sa['phone'],
	  	'mail' => $sa['email'],
	  	'labeledURI' => $sa['websites'],
	  	'ucscPersonPubOfficeLocationDetail' => $sa['officelocation'],
	  	'ucscPersonPubOfficeHours' => $sa['officehours'],
	  	'ucscPersonPubAreaOfExpertise' => $sa['expertise'],
	  	'profLinks' => $sa['profilelinks'],
	  	'ucscPersonPubDescription' => $sa['biography'],
	  	'ucscPersonPubExpertiseReference' => $sa['areas_of_expertise'],
	  	'ucscPersonPubResearchInterest' => $sa['research_interests'],
	  	'ucscPersonPubTeachingInterest' => $sa['teaching_interests'],
	  	'ucscPersonPubAwardsHonorsGrants' => $sa['awards'],
	  	'ucscPersonPubSelectedPublication' => $sa['publications'],
	  	'displayStyle' => $sa['displaystyle'],
	  );
    $result = new FilterProcessResult();
    $result->addAttachments([
      'library' => [
        'ucsc_profiles/profiles-style',
      ],
    ]);
    $result->setProcessedText(\Drupal::service('ucsc_profiles')->render($attrs));
    $result->setCacheMaxAge(30);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>[ucsc_profiles cruzids="cosmo, sammy" name=false]</strong>';
    if ($long) {
      $output[] = $this->t('Adds a section for displaying UCSC profiles. All attributes are optional.') . '</p>';
    } else {
      $output[] = $this->t('Displays profiles') . '</p>';
    }

    return implode(' ', $output);
  }

}
