<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Tests\Admin\Reporting;

use PHPUnit\Framework\TestCase;
use VictorWitkamp\OpenWPSecurity\Core\Admin\Reporting\CountryDistribution;

final class CountryDistributionTest extends TestCase {
	public function test_summarize_keeps_the_leading_countries_and_groups_the_remainder(): void {
		$countries = array();

		foreach ( range( 1, 10 ) as $index ) {
			$countries[] = array(
				'country_code' => 'C' . $index,
				'country_name' => 'Country ' . $index,
				'total'        => 11 - $index,
			);
		}

		$summary = ( new CountryDistribution() )->summarize( $countries, 4 );

		self::assertCount( 4, $summary );
		self::assertSame( 'C3', $summary[2]['country_code'] );
		self::assertSame( 'Other countries', $summary[3]['country_name'] );
		self::assertSame( 28, $summary[3]['total'] );
	}

	public function test_summarize_leaves_short_distributions_unchanged(): void {
		$countries = array(
			array(
				'country_code' => 'NL',
				'country_name' => 'Netherlands',
				'total'        => 5,
			),
		);

		self::assertSame( $countries, ( new CountryDistribution() )->summarize( $countries ) );
	}
}
