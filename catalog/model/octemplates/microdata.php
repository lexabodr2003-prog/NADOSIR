<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesMicrodata extends Model {

    public function generateArticleMicrodata($article_info, $data) {
        if (!is_array($article_info) || !is_array($data)) {
            return json_encode([]);
        }

        $description = '';
        $raw_description = $article_info['description'] ?? '';
        
		if (!empty($raw_description) && is_string($raw_description)) {
			$description = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($raw_description, ENT_QUOTES, 'UTF-8'))));
			$max_length = 400; 
			if (mb_strlen($description, 'UTF-8') > $max_length) {
				$cut_position = mb_strpos($description, '.', $max_length, 'UTF-8');
				if ($cut_position !== false) {
					$description = mb_substr($description, 0, $cut_position + 1, 'UTF-8');
				} else {
					$description = mb_substr($description, 0, $max_length, 'UTF-8') . '...';
				}
			}
		}

        if (empty($article_info['name']) || empty($data['canonical'])) {
            return json_encode([]);
        }

        $microdata = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $this->cleanText($article_info['name']),
            'description' => $description,
            'url' => filter_var($data['canonical'], FILTER_VALIDATE_URL) ? $data['canonical'] : '',
            'datePublished' => $this->formatDate($article_info['date_added']),
            'author' => [
                '@type' => 'Organization',
                'name' => $this->cleanText($data['store_name'] ?? ''),
                'url' => filter_var($data['store_url'] ?? '', FILTER_VALIDATE_URL) ? $data['store_url'] : ''
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->cleanText($data['store_name'] ?? ''),
                'url' => filter_var($data['store_url'] ?? '', FILTER_VALIDATE_URL) ? $data['store_url'] : '',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => filter_var($data['logo_url'] ?? '', FILTER_VALIDATE_URL) ? $data['logo_url'] : ''
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => filter_var($data['canonical'], FILTER_VALIDATE_URL) ? $data['canonical'] : ''
            ],
            'inLanguage' => $this->validateLanguageCode($data['language_code'] ?? 'uk')
        ];

        if (!empty($data['date_modified']) && !empty($article_info['date_added'])) {
            $published_time = strtotime($article_info['date_added']);
            $modified_time = strtotime($data['date_modified']);
            
            if ($published_time !== false && $modified_time !== false && $modified_time >= $published_time) {
                $microdata['dateModified'] = date('c', $modified_time);
            }
        }

        if (!empty($data['popup'])) {
            $image_url = filter_var($data['popup'], FILTER_VALIDATE_URL);
            if ($image_url) {
                $microdata['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                    'width' => max(1, (int)($data['image_width'] ?? 1200)),
                    'height' => max(1, (int)($data['image_height'] ?? 900))
                ];
            }
        }

        if (!empty($data['tags']) && is_array($data['tags'])) {
            $clean_tags = [];
            foreach ($data['tags'] as $tag) {
                if (is_string($tag)) {
                    $clean_tag = trim(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));
                    if (!empty($clean_tag)) {
                        $clean_tags[] = $clean_tag;
                    }
                }
            }
            
            if (!empty($clean_tags)) {
                $microdata['keywords'] = implode(', ', $clean_tags);
            }
        }

        if (isset($data['reviews_total'])) {
            $reviews_count = max(0, (int)$data['reviews_total']);
            if ($reviews_count > 0) {
                $microdata['commentCount'] = $reviews_count;
                $microdata['interactionStatistic'] = [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/CommentAction',
                    'userInteractionCount' => $reviews_count
                ];
            }
        }

		if (isset($article_info['description']) && is_string($article_info['description'])) {
			
			$simple_count = count(array_filter(explode(' ', 
				preg_replace('/\s+/', ' ', 
					trim(strip_tags(html_entity_decode($article_info['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')))
				)
			), function($w) { return !empty(trim($w)); }));

			if ($simple_count > 0) {
                $microdata['wordCount'] = $simple_count;
            }
		}

        return json_encode($microdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

	public function generateHomeMicrodata($data) {
		$org_data = $this->config->get('theme_oct_deals_org_data');
		$oct_data = $this->config->get('theme_oct_deals_data');
		$this->load->model('octemplates/module/oct_sreview_reviews');

		if (empty($oct_data['micro'])) {
			return;
		}

		$lang_id = (int)$this->config->get('config_language_id');
		$https = isset($this->request->server['HTTPS']) && $this->request->server['HTTPS'];
		$server = $https ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$server = rtrim($server, '/') . '/';

		$microdata = [
			'@context' => 'https://schema.org',
			'@type' => $this->getValidStoreType($org_data['type'] ?? 'OnlineStore'),
			'@id' => $server . '#organization',
			'name' => $this->getOrgName($org_data, $lang_id),
			'url' => $server
		];

		if (!empty($org_data['legal_name'])) {
			$microdata['legalName'] = trim($org_data['legal_name']);
		}

		if (!empty($org_data['founding_date'])) {
			$microdata['foundingDate'] = trim($org_data['founding_date']);
		}

		if (!empty($org_data['area_served'])) {
			$areas = array_filter(array_map('trim', explode(',', $org_data['area_served'])));
			if (!empty($areas)) {
				$microdata['areaServed'] = $this->parseAreaServed($areas);
			}
		}

		if (!empty($data['oct_contact_telephones'])) {
			$phones = is_array($data['oct_contact_telephones']) ? $data['oct_contact_telephones'] : [$data['oct_contact_telephones']];
			$formatted_phones = array_filter(array_map([$this, 'formatPhoneForSchema'], $phones));
			if (!empty($formatted_phones)) {
				$microdata['telephone'] = count($formatted_phones) > 1 ? $formatted_phones : $formatted_phones[0];
			}
		}

		if (!empty($data['main_contact_email']) && filter_var($data['main_contact_email'], FILTER_VALIDATE_EMAIL)) {
			$microdata['email'] = trim($data['main_contact_email']);
		}

		$address = ['@type' => 'PostalAddress'];
		$has_address = false;

		if (!empty($org_data['address'])) {
			$address['streetAddress'] = trim($org_data['address']);
			$has_address = true;
		} elseif (!empty($data['contact_address'][$lang_id])) {
			$address['streetAddress'] = trim(strip_tags($data['contact_address'][$lang_id]));
			$has_address = true;
		}

		if ($has_address) {
			if (!empty($org_data['postal_code'])) {
				$address['postalCode'] = trim($org_data['postal_code']);
			}
			if (!empty($org_data['country'])) {
				$address['addressCountry'] = trim($org_data['country']);
			}
			if (!empty($org_data['city'])) {
				$city = explode(',', trim($org_data['city']))[0];
				$address['addressLocality'] = trim($city);
			}
			$microdata['address'] = $address;
		}

		if (!empty($org_data['latitude']) && !empty($org_data['longitude'])) {
			$latitude = (float)trim($org_data['latitude']);
			$longitude = (float)trim($org_data['longitude']);
			if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
				$microdata['geo'] = [
					'@type' => 'GeoCoordinates',
					'latitude' => $latitude,
					'longitude' => $longitude
				];

				$microdata['hasMap'] = "https://www.google.com/maps?q={$latitude},{$longitude}";
			}
		}

		if (!empty($data['oct_contact_opens'])) {
			$opening_hours = $this->formatOpeningHours($data['oct_contact_opens']);
			if (!empty($opening_hours)) {
				$microdata['openingHours'] = $opening_hours;
			}
		}

		if (!empty($org_data['price_range']) && $org_data['price_range'] !== '0') {
			$microdata['priceRange'] = trim($org_data['price_range']);
		}

		$logo_path = $this->config->get('config_logo');
		if ($logo_path && is_file(DIR_IMAGE . $logo_path)) {
			$logo_url = $server . 'image/' . $logo_path;
			$microdata['logo'] = $logo_url;
			$microdata['image'] = $logo_url;
		}

		$social_urls = [];
		if (isset($oct_data['contact_view_socials']) && $oct_data['contact_view_socials'] == 'on' && !empty($oct_data['socials'])) {
			foreach ($oct_data['socials'] as $social) {
				if (!empty($social['link']) && filter_var($social['link'], FILTER_VALIDATE_URL)) {
					$social_urls[] = trim($social['link']);
				}
			}
		}
		if (!empty($social_urls)) {
			$microdata['sameAs'] = array_unique($social_urls);
		}

        if (!empty($org_data['currencies_accepted'])) {
            $currencies = array_filter(array_map('trim', explode(',', $org_data['currencies_accepted'])));
            $valid_currencies = [];

            foreach ($currencies as $currency) {
                if (preg_match('/^[A-Z]{3}$/', $currency)) {
                    $valid_currencies[] = $currency;
                }
            }

            if (!empty($valid_currencies)) {
                $microdata['currenciesAccepted'] = implode(',', $valid_currencies);
            }
        }

        if (!empty($org_data['payment_accepted'])) {
            $payments = array_filter(array_map('trim', explode(',', $org_data['payment_accepted'])));
            $valid_payments = [];

            foreach ($payments as $payment) {
                if (mb_strlen($payment, 'UTF-8') <= 50) {
                    $valid_payments[] = $payment;
                }
            }

            if (!empty($valid_payments)) {
                $microdata['paymentAccepted'] = implode(',', $valid_payments);
            }
        }

		if (!empty($server) && filter_var($server, FILTER_VALIDATE_URL)) {
			$base = rtrim($server, '/') . '/';
			$microdata['potentialAction'] = [
				'@type'       => 'SearchAction',
				'target'      => $base . 'index.php?route=product/search&search={search_term_string}',
				'query-input' => 'required name=search_term_string'
			];
		}

		$contact_url = $this->url->link('information/contact');
		if (!empty($contact_url)) {
			$microdata['contactPoint'] = [
				'@type' => 'ContactPoint',
				'contactType' => 'customer service',
				'url' => $contact_url
			];
			
			if (!empty($data['oct_contact_telephones'])) {
				$phones = is_array($data['oct_contact_telephones']) ? $data['oct_contact_telephones'] : [$data['oct_contact_telephones']];
				$formatted_phones = array_filter(array_map([$this, 'formatPhoneForSchema'], $phones));
				if (!empty($formatted_phones)) {
					$microdata['contactPoint']['telephone'] = count($formatted_phones) > 1 ? $formatted_phones : $formatted_phones[0];
				}
			}
			
			if (!empty($data['main_contact_email']) && filter_var($data['main_contact_email'], FILTER_VALIDATE_EMAIL)) {
				$microdata['contactPoint']['email'] = trim($data['main_contact_email']);
			}
		}

		$data['store_rating'] = round($this->model_octemplates_module_oct_sreview_reviews->getTotalStoreReviews(), 1);

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'sort_order_asc';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = 20;
		}

		$filter_data = [
			'sort' => $sort,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];
		$data['review_total'] = $this->model_octemplates_module_oct_sreview_reviews->getTotalReviews($filter_data);

		if (!empty($data['store_rating']) && !empty($data['review_total'])) {
			$rating = (float)$data['store_rating'];
			$review_count = (int)$data['review_total'];
			
			if ($rating > 0 && $review_count > 0) {
				$microdata['aggregateRating'] = [
					'@type' => 'AggregateRating',
					'ratingValue' => number_format($rating, 1),
					'reviewCount' => $review_count,
					'bestRating' => '5',
					'worstRating' => '1'
				];
			}
		}

		return json_encode($microdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function generateLocationsOrganizationMicrodata($locations) {

		$org_data = $this->config->get('theme_oct_deals_org_data');
		$oct_data = $this->config->get('theme_oct_deals_data');
		
		if (empty($oct_data['micro'])) {
			return;
		}

		if (empty($locations) || !is_array($locations)) {
			return '';
		}

		$microdata_array = [];
		
		$https = isset($this->request->server['HTTPS']) && $this->request->server['HTTPS'];
		$server = $https ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$server = rtrim($server, '/') . '/';

		$logo_path = $this->config->get('config_logo');
		if ($logo_path && is_file(DIR_IMAGE . $logo_path)) {
			$logo_url = $server . 'image/' . $logo_path;
		} else {
			$logo_url = '';
		}

		foreach ($locations as $location) {
			if (empty($location['title']) || empty($location['address'])) {
				continue;
			}

			$location_data = [
				'@context' => 'https://schema.org',
				'@type' => 'Store',
				'name' => $this->cleanText($location['title']),
				'address' => $this->cleanText($location['address']),
					'parentOrganization' => [
						'@type' => 'Organization',
						'name' => $this->cleanText($this->config->get('config_name')),
						'url' => $server,
						'logo' => $logo_url
					]
			];

			if (!empty($org_data['price_range']) && $org_data['price_range'] !== '0') {
				$location_data['priceRange'] = trim($org_data['price_range']);
			}

			if (!empty($location['phone']) && is_array($location['phone'])) {
				$formatted_phones = array_filter(array_map([$this, 'formatPhoneForSchema'], $location['phone']));
				if (!empty($formatted_phones)) {
					$location_data['telephone'] = count($formatted_phones) > 1 ? $formatted_phones : $formatted_phones[0];
				}
			}

			if (!empty($location['open']) && is_array($location['open'])) {
				$formatted_hours = $this->formatOpeningHours($location['open']);
				if (!empty($formatted_hours)) {
					$location_data['openingHours'] = $formatted_hours;
				}
			}

			if (!empty($location['link'])) {
				$location_data['url'] = filter_var($location['link'], FILTER_VALIDATE_URL) ? $location['link'] : '';
			}

			if (!empty($location['thumb'])) {
				$location_data['image'] = filter_var($location['thumb'], FILTER_VALIDATE_URL) ? $location['thumb'] : '';
			}

			$microdata_array[] = $location_data;
		}

		if (empty($microdata_array)) {
			return '';
		}	

		$output = '';
		foreach ($microdata_array as $microdata) {
			$output .= '<script type="application/ld+json">';
			$output .= json_encode($microdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$output .= '</script>' . PHP_EOL;
		}
		
		return $output;
	}

	public function generateReviewPageMicrodata($review_data) {
		$org_data = $this->config->get('theme_oct_deals_org_data');
		$oct_data = $this->config->get('theme_oct_deals_data');
		
		if (empty($oct_data['micro'])) {
			return '';
		}

		$store_name = $this->cleanText($org_data['store_name'] ?? $this->config->get('config_name'));
		$store_url = $this->config->get('config_url');

		$microdata = [
			'@context' => 'https://schema.org',
			'@type' => 'WebPage',
			'name' => $review_data['page_title'] ?? 'Reviews',
			'description' => $review_data['page_description'] ?? '',
			'url' => $review_data['page_url'],
			'mainEntity' => [
				'@type' => 'Organization',
				'@id' => $store_url . '#organization',
				'name' => $store_name,
				'url' => $store_url
			]
		];

		$total_reviews = $review_data['total_reviews'] ?? $review_data['review_total'] ?? 0;
		$average_rating = $review_data['average_rating'] ?? $review_data['store_rating'] ?? 0;

		if ($total_reviews > 0) {
			$microdata['mainEntity']['aggregateRating'] = [
				'@type' => 'AggregateRating',
				'ratingValue' => number_format((float)$average_rating, 1),
				'reviewCount' => (int)$total_reviews,
				'bestRating' => '5',
				'worstRating' => '1'
			];

			if (!empty($review_data['reviews'])) {
				$microdata['mainEntity']['review'] = $this->formatReviewsForSchema($review_data['reviews']);
			}
		}

		return json_encode($microdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private function formatReviewsForSchema($reviews) {
		$formatted_reviews = [];
		
		foreach ($reviews as $review) {
			$clean_review_body = strip_tags($review['text']);
			$clean_review_body = str_replace(["\r\n", "\r", "\n"], ' ', $clean_review_body);
			$clean_review_body = preg_replace('/\s+/', ' ', trim($clean_review_body));

			$formatted_reviews[] = [
				'@type' => 'Review',
				'author' => [
					'@type' => 'Person',
					'name' => $this->cleanText($review['author'])
				],
				'datePublished' => date('Y-m-d', strtotime($review['date_added_microdata'])),
				'reviewBody' => $clean_review_body,
				'reviewRating' => [
					'@type' => 'Rating',
					'ratingValue' => number_format((float)$review['avg_rating_stars'], 1),
					'bestRating' => '5',
					'worstRating' => '1'
				]
			];
		}
		
		return $formatted_reviews;
	}

	private function getAllowedStoreTypes() {
		return [
			'OnlineStore','Store','LocalBusiness','AutoDealer','AutoPartsStore','BookStore','ClothingStore','ComputerStore','DepartmentStore',
			'ElectronicsStore','FloristShop','FurnitureStore','GardenStore','GroceryStore','HardwareStore','HobbyShop','HomeGoodsStore','JewelryStore',
			'LiquorStore','MensClothingStore','MobilePhoneStore','MovieRentalStore','MusicStore','OfficeEquipmentStore','OutletStore','PetStore',
			'ShoeStore','SportingGoodsStore','TireShop','ToyStore','WholesaleStore'
		];
	}

	private function getValidStoreType($type) {
		return in_array($type, $this->getAllowedStoreTypes()) ? $type : 'OnlineStore';
	}

	private function getOrgName($org_data, $lang_id) {
		if (!empty($org_data['name'][$lang_id])) {
			return trim($org_data['name'][$lang_id]);
		}
		$config_name = $this->config->get('config_name');
		return is_string($config_name) ? trim($config_name) : 'Organization';
	}

	private function parseAreaServed($areas) {
		$result = [];
		foreach ($areas as $area) {
			$result[] = strlen($area) === 2 && ctype_upper($area) ? ['@type' => 'Country', 'name' => $area] : ['@type' => 'City', 'name' => $area];
		}
		return $result;
	}

	private function formatPhoneForSchema($phone) {
		$phone = preg_replace('/[^\d+]/', '', trim((string)$phone));
		if (empty($phone)) {
			return null;
		}
		if (strpos($phone, '+') !== 0 && strlen($phone) >= 7) {
			$phone = '+' . $phone;
		}
		return strlen($phone) < 8 ? null : $phone;
	}

	private function formatOpeningHours($hours_data) {
		$result = [];
		$hours = is_array($hours_data) ? $hours_data : [$hours_data];
		
		foreach ($hours as $hour) {
			$formatted = $this->convertToSchemaFormat(trim(str_replace(["\r", "\n"], '', $hour)));
			if ($formatted) {
				$expanded = $this->expandDayRanges($formatted);
				$result = array_merge($result, $expanded);
			}
		}
		
		return array_unique($result);
	}

	private function expandDayRanges($formatted_hour) {
		$day_order = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
		
		if (preg_match('/^([A-Za-z]{2})-([A-Za-z]{2})\s+(.+)$/', $formatted_hour, $matches)) {
			$start_day = $matches[1];
			$end_day = $matches[2];
			$time_range = $matches[3];
			
			$start_index = array_search($start_day, $day_order);
			$end_index = array_search($end_day, $day_order);
			
			if ($start_index === false || $end_index === false) {
				return [$formatted_hour];
			}
			
			$expanded = [];
			$current_index = $start_index;
			
			while (true) {
				$expanded[] = $day_order[$current_index] . ' ' . $time_range;
				
				if ($current_index === $end_index) {
					break;
				}
				
				$current_index = ($current_index + 1) % 7;
				
				if ($current_index === $start_index) {
					break;
				}
			}
			
			return $expanded;
		}
		
		return [$formatted_hour];
	}

	private function convertToSchemaFormat($hour_string) {
		if (empty($hour_string)) {
			return null;
		}

		$day_map = [
			'пн' => 'Mo', 'понеділок' => 'Mo', 'monday' => 'Mo', 'понедельник' => 'Mo', 'poniedziałek' => 'Mo',
			'вт' => 'Tu', 'вівторок' => 'Tu', 'tuesday' => 'Tu', 'вторник' => 'Tu', 'wtorek' => 'Tu',
			'ср' => 'We', 'середа' => 'We', 'wednesday' => 'We', 'среда' => 'We', 'środa' => 'We',
			'чт' => 'Th', 'четвер' => 'Th', 'thursday' => 'Th', 'четверг' => 'Th', 'czwartek' => 'Th',
			'пт' => 'Fr', 'п\'ятниця' => 'Fr', 'пятниця' => 'Fr', 'friday' => 'Fr', 'пятница' => 'Fr', 'piątek' => 'Fr',
			'сб' => 'Sa', 'субота' => 'Sa', 'saturday' => 'Sa', 'суббота' => 'Sa', 'sobota' => 'Sa',
			'нд' => 'Su', 'неділя' => 'Su', 'sunday' => 'Su', 'вс' => 'Su', 'воскресенье' => 'Su', 'niedziela' => 'Su',
			'mon' => 'Mo', 'tue' => 'Tu', 'wed' => 'We', 'thu' => 'Th', 'fri' => 'Fr', 'sat' => 'Sa', 'sun' => 'Su',
			'pn' => 'Mo', 'pt' => 'Fr', 'sb' => 'Sa', 'nd' => 'Su',
			'śr' => 'We', 'cz' => 'Th'
		];

		$normalized = mb_strtolower(trim($hour_string));
		$normalized = preg_replace('/\s+/', ' ', $normalized);
		$normalized = str_replace([',', ';', '.'], '', $normalized);

		if (preg_match('/^(.+?)[\s:]*(з|from|с|od)\s*(\d{1,2}):(\d{2})[\s:]*(до|to|do)\s*(\d{1,2}):(\d{2})/', $normalized, $matches)) {
			$days_part = trim($matches[1]);
			$open_time = sprintf('%02d:%02d', (int)$matches[3], (int)$matches[4]);
			$close_time = sprintf('%02d:%02d', (int)$matches[6], (int)$matches[7]);
			return $this->processDayRangeWithTime($days_part, $open_time, $close_time, $day_map);
		}

		if (preg_match('/^(.+?)[\s:]*(з|from|с|od)\s*(\d{1,2})[\s:]*(до|to|do)\s*(\d{1,2})/', $normalized, $matches)) {
			$days_part = trim($matches[1]);
			$open_hour = (int)$matches[3];
			$close_hour = (int)$matches[5];
			return $this->processDayRange($days_part, $open_hour, $close_hour, $day_map);
		}

		if (preg_match('/^(.+?)[\s:]*(from)\s*(\d{1,2})\s*(am|pm)[\s:]*(to)\s*(\d{1,2})\s*(am|pm)/', $normalized, $matches)) {
			$days_part = trim($matches[1]);
			$open_hour = (int)$matches[3];
			$open_period = $matches[4];
			$close_hour = (int)$matches[6];
			$close_period = $matches[7];

			if ($open_period === 'pm' && $open_hour !== 12) {
				$open_hour += 12;
			} elseif ($open_period === 'am' && $open_hour === 12) {
				$open_hour = 0;
			}

			if ($close_period === 'pm' && $close_hour !== 12) {
				$close_hour += 12;
			} elseif ($close_period === 'am' && $close_hour === 12) {
				$close_hour = 0;
			}

			$open_time = sprintf('%02d:00', $open_hour);
			$close_time = sprintf('%02d:00', $close_hour);
			return $this->processDayRangeWithTime($days_part, $open_time, $close_time, $day_map);
		}

		if (preg_match('/^(.+?)[\s:]*(\d{1,2}):(\d{2})\s*[-–]\s*(\d{1,2}):(\d{2})/', $normalized, $matches)) {
			$days_part = trim($matches[1]);
			$open_time = sprintf('%02d:%02d', (int)$matches[2], (int)$matches[3]);
			$close_time = sprintf('%02d:%02d', (int)$matches[4], (int)$matches[5]);
			return $this->processDayRangeWithTime($days_part, $open_time, $close_time, $day_map);
		}

		if (preg_match('/^(.+?)[\s:]*(\d{1,2})\s*[-–]\s*(\d{1,2})/', $normalized, $matches)) {
			$days_part = trim($matches[1]);
			$open_hour = (int)$matches[2];
			$close_hour = (int)$matches[3];
			return $this->processDayRange($days_part, $open_hour, $close_hour, $day_map);
		}

		if (preg_match('/^(\d{1,2}):?(\d{2})?\s*[-–]\s*(\d{1,2}):?(\d{2})?/', $normalized, $matches)) {
			$open_time = sprintf('%02d:%02d', (int)$matches[1], (int)($matches[2] ?? 0));
			$close_time = sprintf('%02d:%02d', (int)$matches[3], (int)($matches[4] ?? 0));
			return $open_time . '-' . $close_time;
		}

		if (preg_match('/^(\d{1,2})\s*(am|pm)\s*[-–]\s*(\d{1,2})\s*(am|pm)/', $normalized, $matches)) {
			$open_hour = (int)$matches[1];
			$open_period = $matches[2];
			$close_hour = (int)$matches[3];
			$close_period = $matches[4];

			if ($open_period === 'pm' && $open_hour !== 12) {
				$open_hour += 12;
			} elseif ($open_period === 'am' && $open_hour === 12) {
				$open_hour = 0;
			}

			if ($close_period === 'pm' && $close_hour !== 12) {
				$close_hour += 12;
			} elseif ($close_period === 'am' && $close_hour === 12) {
				$close_hour = 0;
			}

			$open_time = sprintf('%02d:00', $open_hour);
			$close_time = sprintf('%02d:00', $close_hour);
			return $open_time . '-' . $close_time;
		}

		return null;
	}

	private function processDayRangeWithTime($days_part, $open_time, $close_time, $day_map) {
		$days_part = str_replace(['-', '–', ' '], ['-', '-', ''], $days_part);

		if (strpos($days_part, '-') !== false) {
			$day_range = explode('-', $days_part);
			if (count($day_range) == 2) {
				$start_day = $day_map[trim($day_range[0])] ?? null;
				$end_day = $day_map[trim($day_range[1])] ?? null;
				if ($start_day && $end_day) {
					return $start_day . '-' . $end_day . ' ' . $open_time . '-' . $close_time;
				}
			}
		} else {
			$single_day = $day_map[$days_part] ?? null;
			if ($single_day) {
				return $single_day . ' ' . $open_time . '-' . $close_time;
			}
		}

		return null;
	}

	private function processDayRange($days_part, $open_hour, $close_hour, $day_map) {
		$open_time = sprintf('%02d:00', $open_hour);
		$close_time = sprintf('%02d:00', $close_hour);
		$time_range = $open_time . '-' . $close_time;
		$days_part = str_replace(['-', '–', ' '], ['-', '-', ''], $days_part);

		if (strpos($days_part, '-') !== false) {
			$day_range = explode('-', $days_part);
			if (count($day_range) == 2) {
				$start_day = $day_map[trim($day_range[0])] ?? null;
				$end_day = $day_map[trim($day_range[1])] ?? null;
				if ($start_day && $end_day) {
					return $start_day . '-' . $end_day . ' ' . $time_range;
				}
			}
		} else {
			$single_day = $day_map[$days_part] ?? null;
			if ($single_day) {
				return $single_day . ' ' . $time_range;
			}
		}

		return null;
	}

    private function formatDate($date_string) {
        $timestamp = strtotime($date_string);
        return $timestamp !== false ? date('c', $timestamp) : date('c');
    }

    private function validateLanguageCode($code) {
        if (!is_string($code) || empty($code)) {
            return 'uk';
        }
        
        $code = strtolower(trim($code));
        
        if (preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $code)) {
            return $code;
        }
        
        return 'uk';
    }

    private function cleanText($text) {
        if (!is_string($text)) {
            return '';
        }
        
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }   
}