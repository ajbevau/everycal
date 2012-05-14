<?php
/**
 * Every Calendar Scheduler: Repeating Pattern Expression
 *
 * Modelled of crontab expressions but with a few extra fun parts
 * see the examples below for broad coverage of the syntax that
 * is supported. It is worth noting that unlike cron which matches
 * on DoM/MoY OR DoW this class requires ALL 3 conditions to be
 * met for a repeat instance to be flagged.
 *
 * Also worth noting Sunday is 1 and Saturday is 7.
 *
 * The Minutes and Hours components have been removed.
 */

// Example Expressions: (using // to allow */ in comments)
//     DoM  MoY  DoW
//      *    *    1		Every Sunday
//      1   */2   *		First day every 2nd month
//      1   3/2   *		First day every 2nd March
//     -1    *    *		Last day of the month
//      *    *   1/3	Every 3rd Sunday
//      *    *   -2		Last Monday every month
//      *   */3  -1/2	2nd last Sunday every 3rd month
//     -2   */6  3/4	2nd last day of every 6 month where it is the 4th Tuesday(3) of the month
// Some more complicated expression examples:
//     DoM    MoY            DoW
//     10-20  *              2			Mondays between 10th and 20th
//     *      2,3,4,5,12     -2			Last Monday of Feb,Mar,Apr,May,Dec
//     5-25   3,4,5,9,10,11  6/1,4		1st and 4th Friday of the month where day is 5th-25th in Autumn/Spring
//     *      */3            -1/1-3		Last, 2nd and 3rd Last Sundays of every 3rd month

/**
 * RepeatExpression Class
 * This class contains static methods for building instances of an expression
 * for commonly repeated types, and also allows an expression to be passed to
 * the constructor to build more complicated expressions.
 */
class EveryCal_RepeatExpression
{
	
	/**
	 * Known TYPES of expressions and a map to their build functions
	 * The build functions will be given the event start and end dates
	 */
	static $TYPES = array(
		// Monthly repeats (i.e. once a month on the nominated day)
		'MONTHLY' => array(
			'func' => 'BuildMonthly', // function to call
			'desc' => 'Monthly or every X months',
			'params' => array(
				'every' => array( 'required' => false, 'desc' => 'Every X months', 'default' => 1 )
			)
		),

		// Weekly repeats (i.e. once a week on the nominated day)
		'WEEKLY' => array(
			'func' => 'BuildWeekly', // function to call
			'desc' => 'Weekly',
			'params' => null // no parameters
		),

		// Last Xday (e.g. sunday) of the month
		'LAST_X_OF_MONTH' => array(
			'func' => 'BuildLastXofMonth', // function to call
			'desc' => 'Last X of month' ,
			'params' => array(
				'day' => array( 'required' => true, 'desc' => 'Day (X) of week',
					'choices' => array( 1=>'Sunday', 2=>'Monday', 3=>'Tuesday', 4=>'Wednesday',
						5=>'Thursday', 6=>'Friday', 7=>'Saturday' )
				)
			)
		),

		// Once a year
		'YEARLY' => array(
			'func' => 'BuildYearly', // function to call
			'desc' => 'Yearly',
			'params' => null // no parameters
		)
	);
	
	/**
	 * Private string for storing the internal expression
	 */
	private $internal_expression = null;
	
	/**
	 * Private mapping of the repeat sets and period filter sets.
	 * A null value means ALL so by default this will repeat every day.
	 */
	private $when_sets = array(
		'DoM' => array( 'repeat' => null ),
		'MoY' => array( 'repeat' => null, 'period' => null ),
		'DoW' => array( 'repeat' => null, 'period' => null ),
	);
	
	/**
	 * Build Function (static)
	 * Constructs an instance of the repeat expression for the given type
	 * by calling one of the private static methods with the parameters,
	 * then calling the constructor with the expression string returned.
	 *
	 * @param $type One of the keys from EveryCal_RepeatExpression.TYPES
	 * @param $params Keyed array of parameters to pass to the type build function
	 * @return An instance of EveryCal_RepeatExpression or null on failure
	 */
	public static function Build( $type, $params=array() )
	{
		if ( ! array_key_exists( $type, self::$TYPES ) ) 
			return null;
		$func = self::$TYPES[$type]['func'];
		$expr = self::$func( $params );
		if ( null == $expr )
			return null; // failed to build an expression string
		return new EveryCal_RepeatExpression( $expr );
	}
	
	/**
	 * EveryCal_RepeatExpression Constructor
	 * Returns a new instance which will match times using the given expression
	 * string. If the expression string can not be parsed then an Exception will
	 * be thrown (just a generic PHP Exception).
	 *
	 * @param $crontab The string expression to build the instance out of
	 * @return A new instance of EveryCal_RepeatExpression
	 */
	public function __construct( $crontab )
	{
		if ( ! $this->ParseParts( $crontab ) )
			throw new Exception( sprintf( __( 'Failed to parse the crontab %s' ), $crontab ) );
		
		// Cache the crontab expression
		$this->internal_expression = $crontab;
	}
	
	/**
	 * ParseParts Function (private)
	 * Parses the given crontab expression into parts that the matching function
	 * uses and stores them into $this->parts. If the expression is successfully
	 * parsed returns true otherwise returns false.
	 * 
	 * @param $crontab The expression to parse
	 * @return True of False indicating if parsing was successful
	 */
	private function ParseParts( $crontab )
	{
		// All valid crontab expressions should have 3 tokens that are
		// whitespace separated this function parses each token in turn.
		// But first check if there are 3 valid tokens.
		$regexp_crontab = '/^([0-9,\-\*])\s+' + // note slight difference no / allowed
							'([0-9,\/\*])\s+' + // note slight difference no - allowed
							'([0-9,\/\-\*])$/';
		$crontab = trim( $contab );
		$matches = array();
		if ( ! preg_match( $regexp_crontab, $crontab, $matches ) )
			return false;
		
		// FIRST TOKEN: Day of Month
		if ( ! $this->ParseDayOfMonth( $matches[1] ) )
			return false;
		
		// SECOND TOKEN: Month of Year
		if ( ! $this->ParseMonthOfYear( $matches[2] ) )
			return false;
		
		// THIRD TOKEN: Day of Week
		if ( ! $this->ParseDayOfWeek( $matches[3] ) )
			return false;
		
		// All good
		return true;
	}
	
	/**
	 * ParseDayOfMonth Function (private)
	 * Parses the given substring of a crontab expression as the DoM component.
	 *
	 * @param $crontab The crontab DoM expression.
	 * @return True or False if DoM are successfully parsed.
	 */
	private function ParseDayOfMonth( $crontab )
	{
		// This expression will be one of three types:
		if ( preg_match( '/^\*$/', $crontab ) ) {
			$this->when_sets['DoM']['repeat'] = null; // all DoM
		} else if ( preg_match( '/^\-?\d+[,\-?\d+]?$/', $crontab ) ) {
			$this->when_sets['DoM']['repeat'] = explode( ',', $crontab ); // specific days
		} else if ( preg_match( '/^\d+\-\d+$/', $crontab ) ) {
			$parts = explode( '-', $crontab ); // will have exactly two
			$this->when_sets['DoM']['repeat'] = array();
			for ( $i=$parts[0]; $i<=$parts[1]; $i++ )
				$this->when_sets['DoM']['repeat'][] = $i; // all days in a range
		} else {
			return false; // couldn't parse
		}
		
		// Check the values given make sense
		if ( is_array( $this->when_sets['DoM']['repeat'] ) ) {
			foreach ( $this->when_sets['DoM']['repeat'] as $v ) {
				if ( ! is_numeric( $v ) || $v < -31 || $v > 31 )
					return false;
			}
		}
		
		// As good as can be
		return true;
	}
	
	/**
	 * ParseMonthOfYear Function (private)
	 * Parses the given substring of a crontab expression as the MoY component.
	 *
	 * @param $crontab The crontab MoY expression.
	 * @return True or False if MoY are successfully parsed.
	 */
	private function ParseMonthOfYear( $crontab )
	{
		// This expression will either have a period set or will not
		// for MoY expressions the period set effectively says only
		// include every X occurance (where X is in period set) of 
		// the items in the repeat set.
		$slashpos = strpos( $crontab, '/' );
		$repeat_string = $slashpos !== false ? substr( $crontab, 0, $slashpos ) : $crontab;
		$period_string = $slashpos !== false ? substr( $crontab, $slashpos+1, strlen( $crontab ) - $slashpos - 1 ) : '';
		
		// Repeat string will be either a *, single number or comma separated
		if ( preg_match( '/^\*$/', $repeat_string ) ) {
			$this->when_sets['MoY']['repeat'] = null;
		} else if ( preg_match( '/^\d+[,\d+]?$/', $repeat_string ) ) {
			$this->when_sets['MoY']['repeat'] = explode( ',', $repeat_string );
		} else {
			return false; // couldn't parse
		}
		
		// Is there a period string to parse?
		if ( $slashpos !== false ) {
			// Period string will be a single or comma separated set
			if ( preg_match( '/^\d+[,\d+]?$/', $period_string ) ) {
				$this->when_sets['MoY']['period'] = explode( ',', $period_strin ); // specific intervals
			} else {
				return false; // couldn't parse
			}
		} else { // $slashpos === false
			$this->when_sets['MoY']['period'] = null;
		}
		
		// Validate we have sensible month values
		if ( is_array( $this->when_sets['MoY']['repeat'] ) ) {
			foreach ( $this->when_sets['MoY']['repeat'] as $v )
				if ( ! is_numeric( $v ) || $v < 1 || $v > 12 )
					return false; // invalid month
		}
		if ( is_array( $this->when_sets['MoY']['period'] ) ) {
			foreach ( $this->when_sets['MoY']['period'] as $v )
				if ( ! is_numeric( $v ) || $v < 1 || $v > 12 )
					return false; // invalid month
		}
		
		// All good
		return true;
	}
	
	/**
	 * ParseDayOfWeek Function (private)
	 * Parses the given substring of a crontab expression as the DoW component.
	 *
	 * @param $crontab The crontab DoW expression.
	 * @return True or False if DoW are successfully parsed.
	 */
	private function ParseDayOfWeek( $crontab )
	{
		// TODO: Implement ParseDayOfWeek function
	}
	
	/**
	 * GetRepeatsBetween Function
	 * Returns an array of all the start timestamps (at GMT/UTC/Zulu) that this
	 * expression matches which are between the given start and end DateTimes.
	 *
	 * @param $start DateTime object starting the range to look for matches in
	 * @param $end DateTime objects ending the range to look for matches in
	 * @return Array of start timestamps (int) matched for the range if there are
	 *         no matches for the range the array will be empty. If the array can
	 *         not be build will return null.
	 */
	public function GetRepeatsBetween( $startdt, $enddt )
	{
		// TODO: Implement the GetRepeatsBetween function
		return array();
	}
	
	/**
	 * GetExpression Function
	 * Returns the crontab expression that represents this instance.
	 *
	 * @return The crontab expression string for this instance.
	 */
	public function GetExpression()
	{
		return $this->internal_expression;
	}
	
	/**
	 * BuildMonthly (private)
	 * Builds an expression that represents an event repeating X months by month
	 * on a given day of the month. The most simple examples of this would be
	 *  1) On the 1st of every month; or
	 *  2) On the 10th of every 3rd month.
	 *
	 * The $params array should contain the following keys:
	 *  day   => The day of the month event occurs on
	 *  every => (optional) The frequency of months 1=every, 2=every 2nd, and so on..
	 *
	 * @param $params The keyed parameter array
	 * @return String representation of the monthly repeating event
	 */
	private static function BuildMonthly( $params=array( ) )
	{
		// Make sure that the day parameter has been given
		if ( ! array_key_exists( 'day', $params ) || ! is_numeric( $params['day'] ) )
			return null;
		
		// Every Y months on the same X day is
		//     X  *  Y
		$str = sprintf( '%s * %s', 
					$params['day'],
					array_key_exists( 'every', $params ) && is_numeric( $params['every'] ) ? $params['every'] : '*' );
		return $str;
	}
	
}

?>
