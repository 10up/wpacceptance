<?php
/**
 * Test contact form is functioning properly
 * Located on /contact/
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class ContactFormTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * @testdox When I don't fill out required fields, the contact form wont submit.
	 */
	public function testRequiredFieldsFail() {
		$I = $this->openBrowserPage();
		$I->moveTo( '/contact' );

		$I->click( 'form .submit-wrap input' );

		$I->seeText( 'Please correct errors before submitting this form', null, "Don't see required field error text." );
	}

	/**
	 * @testdox When I fill out the form and submit, a new entry of the proper post type appears in the database.
	 */
	public function testSubmit() {
		$last_id = self::getLastPostId( [ 'post_type' => 'nf_sub' ] );

		$I = $this->openBrowserPage();
		$I->moveTo( '/contact' );

		$I->fillField( '#nf-field-1', 'John' );
		$I->fillField( '#nf-field-2', 'test@test.com' );
		$I->fillField( '#nf-field-3', 'message' );

		$I->click( 'form .submit-wrap input' );
		$I->waitUntilElementVisible( '.nf-response-msg' );

		self::assertNewPostsExist( $last_id, [ 'post_type' => 'nf_sub' ], 'No new contact form entries in database.' );
	}

}
