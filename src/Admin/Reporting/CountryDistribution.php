<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting;

final class CountryDistribution {
	public function summarize( array $countries, int $limit = 8 ): array {
		$limit = max( 2, $limit );

		if ( count( $countries ) <= $limit ) {
			return $countries;
		}

		$visible = array_slice( $countries, 0, $limit - 1 );
		$other   = array_slice( $countries, $limit - 1 );
		$total   = array_sum(
			array_map(
				static function ( array $country ): int {
					return (int) ( $country['total'] ?? 0 );
				},
				$other
			)
		);

		$visible[] = array(
			'country_code' => '',
			'country_name' => 'Other countries',
			'total'        => $total,
		);

		return $visible;
	}
}
