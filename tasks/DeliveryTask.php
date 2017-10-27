<?php

class DeliveryTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		echo "\nQUERING\n";

		// get data from tables
		$connection = new Connection();
		$deliveryData = $connection->query("
			SELECT
				user,
				mailbox,
				service as request_service,
				subservice as request_subservice,
				query as request_query,
				inserted as request_date,
				domain as request_domain,
				webhook as environment,
				messageid as email_id,
				subject as email_subject,
				body as email_body,
				IF(attachments=0,NULL,'yes') as email_attachments,
				code as delivery_code,
				message as delivery_message,
				NULl as delivery_method,
				sent as delivery_date,
				response_time as process_time
			FROM __delete_delivery_received A
			LEFT JOIN __delete_utilization B
			ON A.id = B.email_id
			ORDER BY request_date ASC");

		// get the total rows
		$total = count($deliveryData);
		$current = 1;

		echo "STARTING\n";

		// add data to delivery table
		foreach ($deliveryData as $data)
		{
			$user = $data->user;
			$mailbox = $data->mailbox;
			$request_service = $data->request_service;
			$request_subservice = $data->request_subservice;
			$request_query = $data->request_query;
			$request_date = $data->request_date;
			$request_domain = $data->request_domain;
			$environment = $data->environment;
			$email_id = $data->email_id;
			$email_subject = $data->email_subject;
			$email_body = $data->email_body;
			$email_attachments = $data->email_attachments;
			$delivery_code = $data->delivery_code;
			$delivery_message = $data->delivery_message;
			$delivery_method = $data->delivery_method;
			$delivery_date = empty($data->delivery_date) ? 'NULL' : "'$data->delivery_date'";
			$process_time = empty($data->process_time) ? '00:00:00' : $data->process_time;

			// display procesing
			echo "procesing $current/$total\n";

			// save row to the db
			$connection->query("INSERT INTO delivery (user, mailbox, request_service, request_subservice, request_query, request_date, request_domain, environment, email_id, email_subject, email_body, email_attachments, delivery_code, delivery_message, delivery_method, delivery_date, process_time)
			VALUES ('$user','$mailbox','$request_service','$request_subservice','$request_query','$request_date','$request_domain','$environment','$email_id','$email_subject','$email_body','$email_attachments','$delivery_code','$delivery_message','$delivery_method',$delivery_date,'$process_time')");

			// create log entry
			$errorPath = dirname(__DIR__) . "/logs/delivery.log";
			file_put_contents($errorPath, "DATE:$request_date | USER:$user | MAILBOX:$mailbox\n", FILE_APPEND);

			// increase current
			$current++;
		}

		echo "DONE\n";
	}
}
