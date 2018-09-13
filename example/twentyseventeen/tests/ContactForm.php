<?php
/**
 * Test contact form is functioning properly
 * Located on /contact/
 *
 * @package wpassure
 */

/**
 * PHPUnit test class
 */
class ContactFormTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * When I don't fill out required fields, contact form wont submit
	 */
	public function testRequiredFieldsFail() {
		$I = $this->getAnonymousUser();
		$I->moveTo( '/contact' );

		$I->click( 'form .submit-wrap input' );

		$I->seeText( 'Please correct errors before submitting this form', null, "Don't see required field error text." );
	}

	/**
	 * When I fill out the form and submit, a new entry of the proper post type appears in the database
	 */
	public function testSubmit() {
		$lastId = self::getLastPostId( [ 'post_type' => 'nf_sub' ] );

		$I = $this->getAnonymousUser();
		$I->moveTo( '/contact' );

		$I->fillField( '#nf-field-1', 'John' );
		$I->fillField( '#nf-field-2', 'test@test.com' );
		$I->fillField( '#nf-field-3', 'message' );

		$I->click( 'form .submit-wrap input' );
		$I->waitUntilElementVisible( '.nf-response-msg' );

		self::assertNewPostsExist( $lastId, [ 'post_type' => 'nf_sub' ], 'No new contact form entries in database.' );
	}

}
