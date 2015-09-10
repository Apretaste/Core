<?php
	
	require_once dirname(__FILE__).'/base/ML_Rest.php';
	
	class ML_Campaigns extends ML_Rest
	{
		function ML_Campaigns( $api_key )
		{	
			$this->name = 'campaigns';

			parent::__construct($api_key);
		}

		function getRecipients( $data = null )
		{
			$this->path .= 'recipients/';

			return $this->execute( 'GET', $data );
		}

		function getOpens( $data = null )
		{
			$this->path .= 'opens/';

			return $this->execute( 'GET', $data );
		}

		function getClicks( $data = null )
		{
			$this->path .= 'clicks/';

			return $this->execute( 'GET', $data );
		}

		function getUnsubscribes( $data = null )
		{
			$this->path .= 'unsubscribes/';

			return $this->execute( 'GET', $data );
		}

		function getBounces( $data = null )
		{
			$this->path .= 'bounces/';

			return $this->execute( 'GET', $data );
		}

		function getJunk( $data = null )
		{
			$this->path .= 'junk/';

			return $this->execute( 'GET', $data );
		}
	}

?>