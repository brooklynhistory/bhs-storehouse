<?php

class BHS_Storehouse_Tests_Record extends WP_UnitTestCase {
	protected $data = array(
		'type' => array( 'sound', 'original', 'cultural' ),
		'title' => array( 'Oral History', 'Audio' ),
		'description' => array( 'Oral History Interview with Melinda Broman

Melinda Broman, a White woman, was forty-seven years old when she was interviewed. Her husband Victor Ockey, who was a hemophiliac, died of acquired immune deficiency syndrome (AIDS) in 1989. He was infected with human immunodeficiency virus (HIV) during a routine blood transfusion in the early 1980s. At the time of this interview, Broman had lived in Brooklyn&apos;s Cobble Hill neighborhood since the mid-1980s. Broman&apos;s involvement with AIDS was extensive, reaching beyond her relationship with her husband. As a member of the National Hemophiliac Association she has lobbied for increased awareness of the specific plight of HIV+ hemophiliacs. Through her work as a psychologist at Downstate Medical Center and as a member of the Brooklyn Psychological Association she has organized AIDS workshops. She was interviewed primarily because of her personal and professional experience with AIDS issues specific to hemophiliacs.

In the interview, Melinda Broman was able to provide a lot of information about what it was to be a heterosexual woman within a straight relationship dealing with HIV/AIDS. She speaks about the lack of support within the AIDS movement and also the lack of support from the hemophiliac community. While Broman found herself often surrounded by gay men within the AIDS movement, she made friends but was not always getting the support she needed. It was different within the hemophiliac community. She felt that fear of HIV/AIDS made the virus an unwanted topic of discussion. Melinda also speaks about being in a serodiscordant couple, the feelings she had about that, and how it related to her dealing with her own non-HIV-related health issues. Interview conducted by Robert Sember.

The AIDS/Brooklyn Oral History Project collection includes oral histories conducted for an exhibition undertaken by the Brooklyn Historical Society in 1993. The project attempted to document the impact of the AIDS epidemic on Brooklyn communities. Recordings initially made on magnetic tape concerned the epidemic and were with narrators who had firsthand experience with the crisis in their communities, families and personal life. Narrators came from diverse backgrounds within Brookyn and the New York metropolitan area and had unique experiences which connected them with HIV/AIDS. Substantive topics of hemophilia, sexual behavior, substance abuse, medical practice, social work, homelessness, activism, childhood, relationships and parenting run through at least one, and often several, of the oral histories in the collection.' ),
		'subject' => array( 'Audio', 'AIDS (Disease)', 'AIDS activists', 'Blood coagulation factors', 'Funeral rites and ceremonies', 'Hemophilia', 'Hemophiliacs', 'HIV infections', 'Immunological deficiency syndromes', 'Marriage customs and rites', 'Brooklyn (New York, N.Y.)', 'New York (N.Y.)', 'Melinda Broman' ),
		'creator' => array( 'Melinda Broman' ),
		'contributor' => array( 'Robert Sember' ),
		'publisher' => array( 'Brooklyn Historical Society' ),
		'date' => array( '1992/06/20' ),
		'identifier' => array( '1993.001.01' ),
		'language' => array( 'English' ),
		'coverage' => array( '1992 - 1992' ),
		'coverage' => array( 'Interview place:Brooklyn, New York, N.Y.' ),
		'rights' => array( 'Access is available onsite at Brooklyn Historical Society&apos;s Othmer Library and the Oral History Portal. Use of oral histories other than for private study, scholarship, or research requires permission from BHS by contacting library@brooklynhistory.org.' ),
	);

	public function test_set_up_from_raw_atts() {
		$record = new BHS\Storehouse\Record();

		$this->assertTrue( $record->set_up_from_raw_atts( $this->data ) );

		foreach ( $this->data as $k => $v ) {
			$this->assertSame( $v, $record->get_dc_metadata( $k, false ) );
		}
	}

	public function test_save_should_return_post_id() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$found = $record->save();

		$this->assertInternalType( 'int', $found );
	}

	public function test_save_should_create_post_title() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$post_id = $record->save();

		$post = get_post( $post_id );

		$expected = '1993.001.01 - Oral History';

		$this->assertSame( $expected, $post->post_title );
	}

	public function test_save_should_create_post_content() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$post_id = $record->save();

		$post = get_post( $post_id );

		$expected = $this->data['description'][0];

		$this->assertSame( $expected, $post->post_content );
	}

	public function test_save_should_create_post_name_from_identifier() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$post_id = $record->save();

		$post = get_post( $post_id );

		$expected = sanitize_title( $this->data['identifier'][0] );

		$this->assertSame( $expected, $post->post_name );
	}

	public function test_save_should_create_subject_terms() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$post_id = $record->save();

		$post = get_post( $post_id );

		$found = wp_get_object_terms( $post_id, 'bhssh_subject' );
		$names = array();
		foreach ( $found as $f ) {
			$names[] = $f->name;
		}

		$this->assertEqualSets( $names, $this->data['subject'] );
	}

	public function test_save_should_store_dc_metadata() {
		$record = new BHS\Storehouse\Record();
		$record->set_up_from_raw_atts( $this->data );

		$post_id = $record->save();

		$r2 = new BHS\Storehouse\Record( $post_id );

		foreach ( $this->data as $k => $v ) {
			$this->assertEqualSets( $v, $r2->get_dc_metadata( $k, false ) );
		}
	}
}
