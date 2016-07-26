<?php

class BHS_Storehouse_Tests_Record extends WP_UnitTestCase {
	public function test_set_up_from_raw_atts() {
		$record = new BHS\Storehouse\Record();

		$data = array(
			'collection' => 'AIDS/Brooklyn Oral History Project collection',
			'udf22' => '<a href="http://dlib.nyu.edu/findingaids/html/bhs/arms_1993_001_aids-brooklyn" target="_blank">AIDS-Brooklyn Oral History Project collection (1993.001)</a>',
			'date' => '1992/06/20',
			'extent' => '',
			'exp_5' => '0',
			'imagefile' => '076\199300101.JPG',
			'intvdate' => '1992-06-20',
			'intvplace' => 'Brooklyn, New York, N.Y.',
			'intvrest' => 'Unrestricted.',
			'interviewr' => 'Robert Sember',
			'legal' => "Access is available onsite at Brooklyn Historical Society's Othmer Library and the Oral History Portal. Use of oral histories other than for private study, scholarship, or research requires permission from BHS by contacting library@brooklynhistory.org.",
			'intvlength' => '02:15:06',
			'narrator' => 'Melinda Broman',
			'zsorter' => '019930000100001',
			'objectid' => '1993.001.01',
			'descrip' => "Oral History Interview with Melinda Broman

			Melinda Broman, a White woman, was forty-seven years old when she was interviewed. Her husband Victor Ockey, who was a hemophiliac, died of acquired immune deficiency syndrome (AIDS) in 1989. He was infected with human immunodeficiency virus (HIV) during a routine blood transfusion in the early 1980s. At the time of this interview, Broman had lived in Brooklyn's Cobble Hill neighborhood since the mid-1980s. Broman's involvement with AIDS was extensive, reaching beyond her relationship with her husband. As a member of the National Hemophiliac Association she has lobbied for increased awareness of the specific plight of HIV+ hemophiliacs. Through her work as a psychologist at Downstate Medical Center and as a member of the Brooklyn Psychological Association she has organized AIDS workshops. She was interviewed primarily because of her personal and professional experience with AIDS issues specific to hemophiliacs.

			In the interview, Melinda Broman was able to provide a lot of information about what it was to be a heterosexual woman within a straight relationship dealing with HIV/AIDS. She speaks about the lack of support within the AIDS movement and also the lack of support from the hemophiliac community. While Broman found herself often surrounded by gay men within the AIDS movement, she made friends but was not always getting the support she needed. It was different within the hemophiliac community. She felt that fear of HIV/AIDS made the virus an unwanted topic of discussion. Melinda also speaks about being in a serodiscordant couple, the feelings she had about that, and how it related to her dealing with her own non-HIV-related health issues. Interview conducted by Robert Sember.

			The AIDS/Brooklyn Oral History Project collection includes oral histories conducted for an exhibition undertaken by the Brooklyn Historical Society in 1993. The project attempted to document the impact of the AIDS epidemic on Brooklyn communities. Recordings initially made on magnetic tape concerned the epidemic and were with narrators who had firsthand experience with the crisis in their communities, families and personal life. Narrators came from diverse backgrounds within Brookyn and the New York metropolitan area and had unique experiences which connected them with HIV/AIDS. Substantive topics of hemophilia, sexual behavior, substance abuse, medical practice, social work, homelessness, activism, childhood, relationships and parenting run through at least one, and often several, of the oral histories in the collection.",
			'sterms' => 'Brooklyn (New York, N.Y.)
New York (N.Y.)',
			'subjects' => 'AIDS (Disease)
AIDS activists
Blood coagulation factors
Funeral rites and ceremonies
Hemophilia
Hemophiliacs
HIV infections
Immunological deficiency syndromes
Marriage customs and rites',
			'title' => '',
			'udf21' => '',
			'exp_21' => 'AIDS/BROOKLYN ORAL HISTORY PROJECT COLLECTION',
			'exp_22' => '1993.001.01',
		);

		$this->assertTrue( $record->set_up_from_raw_atts( $data ) );

		$this->assertSame( 'Oral History Interview with Melinda Broman', $record->get( 'title' ) );
	}

	public function test_generate_title_should_prefer_nonempty_title() {
		$atts = array(
			'title' => 'Foo',
			'descrip' => 'Bar',
		);

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_title( $atts );

		$this->assertSame( 'Foo', $generated );
	}

	public function test_generate_title_should_ignore_whitespace_title() {
		$atts = array(
			'title' => '   ',
			'descrip' => 'Bar',
		);

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_title( $atts );

		$this->assertSame( 'Bar', $generated );
	}

	public function test_generate_title_should_take_first_sentence_of_description() {
		$atts = array(
			'title' => '',
			'descrip' => 'This is a long first sentence. This is a long second sentence.',
		);

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_title( $atts );

		$this->assertSame( 'This is a long first sentence', $generated );
	}

	public function test_generate_title_should_take_first_line_of_description() {
		$atts = array(
			'title' => '',
			'descrip' => 'This is a long first sentence
This is a long second sentence.',
		);

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_title( $atts );

		$this->assertSame( 'This is a long first sentence', $generated );
	}

	public function test_generate_multiples_should_explode_on_line_break() {
		$string = "foo
bar
baz";

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_multiples( $string );

		$expected = array( 'foo', 'bar', 'baz' );

		$this->assertSame( $expected, $generated );
	}

	public function test_generate_multiples_should_ignore_empty_lines() {
		$string = "foo

baz";

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_multiples( $string );

		$expected = array( 'foo', 'baz' );

		$this->assertSame( $expected, $generated );
	}

	public function test_generate_multiples_should_trim_lines() {
		$string = "foo
  bar
		baz	";

		$r = new \BHS\Storehouse\Record();

		$generated = $r->generate_multiples( $string );

		$expected = array( 'foo', 'bar', 'baz' );

		$this->assertSame( $expected, $generated );
	}
}
