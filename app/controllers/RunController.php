<?php

use Phalcon\Mvc\Controller;

class RunController extends Controller
{
	public function indexAction()
	{
		echo "Cannot run directly. Please access to run/display or run/api instead";
	}

	/**
	 * Executes an html request the outside. Display the HTML on screen
	 * 
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 * @get String $body, body of the email
	 * */
	public function displayAction()
	{
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");

		$result = $this->renderResponse("html@apretaste.com", $subject, "HTML", $body, array(), "html");
		if($this->di->get('environment') == "sandbox") $result .= '<div style="color:white; background-color:red; font-weight:bold; display:inline-block; padding:5px; position:absolute; top:10px; right:10px; z-index:99">SANDBOX</div>';
		echo $result;
	}

	/**
	 * Executes an API request. Display the JSON on screen
	 * 
	 * @author salvipascual
	 * @get String $subject, subject line of the email
	 * @get String $body, body of the email
	 * */
	public function apiAction()
	{
		$subject = $this->request->get("subject");
		$body = $this->request->get("body");
		$email = $this->request->get("email");
		$attachments = $this->request->get("attachments");
		if(empty($email)) $email = "api@apretaste.com";

		// create attachment as an object
		$attach = array();
		if ( ! empty($attachments))
		{
			// save image into the filesystem
			$wwwroot = $this->di->get('path')['root'];
			$utils = new Utils();
			$filePath = "$wwwroot/temp/".$utils->generateRandomHash().".jpg";
			$content = file_get_contents($attachments);
			imagejpeg(imagecreatefromstring($content), $filePath);

			// optimize the image and grant full permits
			$utils->optimizeImage($filePath);
			chmod($filePath, 0777);

			// create new object
			$object = new stdClass();
			$object->path = $filePath;
			$object->type = image_type_to_mime_type(IMAGETYPE_JPEG);
			$attach = array($object);
		}

		// update last access time to current and set remarketing
		$connection = new Connection();
		$connection->deepQuery("
			START TRANSACTION;
			UPDATE person SET last_access=CURRENT_TIMESTAMP WHERE email='$email';
			UPDATE remarketing SET opened=CURRENT_TIMESTAMP WHERE opened IS NULL AND email='$email';
			COMMIT;");

		// some services cannot be called using the API
		$service = strtoupper(explode(" ", $subject)[0]);
		if ($service == 'EXCLUYEME')
		{
			die("You cannot execute service $service from the API");
		}

		$result = $this->renderResponse($email, $subject, "API", $body, $attach, "json");

		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");
		echo $result;
	}

	/**
	 * Receives email from the Mandrill webhook and send it to be parsed 
	 * 
	 * @author salvipascual
	 * @post json mandrill_events
	 * */
	public function mandrillAction()
	{
		// get the mandrill json structure from the post
//		$mandrill_events = '[{"event":"inbound","ts":1461382970,"msg":{"raw_msg":"Received: from mail-wm0-f46.google.com (unknown [74.125.82.46])\n\tby relay-1.us-west-2.relay-prod (Postfix) with ESMTPS id 3864EE065E\n\tfor <webhook@apretaste.net>; Sat, 23 Apr 2016 03:42:50 +0000 (UTC)\nReceived: by mail-wm0-f46.google.com with SMTP id u206so51783694wme.1\n        for <webhook@apretaste.net>; Fri, 22 Apr 2016 20:42:50 -0700 (PDT)\nX-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed\/relaxed;\n        d=1e100.net; s=20130820;\n        h=x-gm-message-state:delivered-to:dkim-signature:mime-version:date\n         :message-id:subject:from:to;\n        bh=N+GjHTHZxxbyh7rKn0TtOIiqsh2+6+mB9OuLkazjFbw=;\n        b=FXpvdm4cg2uq1fp8ltQtHS75PMBMYkjqfqoDJ3lvl3TCKev\/+NwNbxtfcT9BgrYpCL\n         UZ6VQOEQVxwH+XcPsbex4SOTwevl76BPX4xwv1EjEeAPULl17dNcg8fSny1r1efWpumn\n         uPla\/abE8ANA3E9wtyVSh2B8mIW5AKT1\/VDxoYMKg6R5CcS3MYraBFNmgJyopiMir3XY\n         gFSF+2RQC0gEf8CEXekOFHcUBvgw0JQ6AyRQfb2L9rRauRBOKdjmLgB+0qfliIMz\/eUy\n         \/sHI7ztw7csSKTMB0iYADULw2vII3mNtSvhLs8k5tNz7JEgteLfTabsELUnOVMzPVa6s\n         5V3A==\nX-Gm-Message-State: AOPr4FW6k1zx3b2WUEjv7ejWgGI\/tz6eOyr1N1kuytV9po7LTjfaW0NNRaFR9bOw2AKvb+tXjJ1LY662TWmtTD\/pOIiOFLA=\nX-Received: by 10.28.9.139 with SMTP id 133mr546157wmj.72.1461382969050;\n        Fri, 22 Apr 2016 20:42:49 -0700 (PDT)\nX-Forwarded-To: webhook@apretaste.net\nX-Forwarded-For: apretaste@gmail.com webhook@apretaste.net\nDelivered-To: apretaste@gmail.com\nReceived: by 10.28.5.132 with SMTP id 126csp1069965wmf;\n        Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nX-Received: by 10.37.10.4 with SMTP id 4mr15263290ybk.160.1461382966762;\n        Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nReceived: from mail-yw0-x229.google.com (mail-yw0-x229.google.com. [2607:f8b0:4002:c05::229])\n        by mx.google.com with ESMTPS id f185si2764353yba.282.2016.04.22.20.42.46\n        for <apretaste@gmail.com>\n        (version=TLS1_2 cipher=ECDHE-RSA-AES128-GCM-SHA256 bits=128\/128);\n        Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nReceived-SPF: pass (google.com: domain of salvi.pascual@gmail.com designates 2607:f8b0:4002:c05::229 as permitted sender) client-ip=2607:f8b0:4002:c05::229;\nAuthentication-Results: mx.google.com;\n       dkim=pass header.i=@gmail.com;\n       spf=pass (google.com: domain of salvi.pascual@gmail.com designates 2607:f8b0:4002:c05::229 as permitted sender) smtp.mailfrom=salvi.pascual@gmail.com;\n       dmarc=pass (p=NONE dis=NONE) header.from=gmail.com\nReceived: by mail-yw0-x229.google.com with SMTP id t10so136409743ywa.0\n        for <apretaste@gmail.com>; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed\/relaxed;\n        d=gmail.com; s=20120113;\n        h=mime-version:date:message-id:subject:from:to;\n        bh=N+GjHTHZxxbyh7rKn0TtOIiqsh2+6+mB9OuLkazjFbw=;\n        b=ZBeB2ZxjRtgzZ8J1nEk5JD\/Sm6wzeF3iKwqiJKEmMTNFRWMxjTS2InuN3QLgf+55xJ\n         cefPyp2dUN39HhXzNPJ1SHiEbzxHZuAS1vESI+scmxQ3Ve\/mEddJyLjvcWdFNmmnrXtg\n         XMoy72Sy1TtxycaylkMdnh7tj2iuPUn6WaAoFHL100HzXNpMmJy1HA2endeWRiNwU8LT\n         TGFkRTfIxN+385fHw7Yzvyg14tI1gflSI41YI0qGITHPE6DFavCohDSdWu0khGvt0Fdx\n         dkubQVIHlCcZT1meW5lWuziwFrcRxcqCwRTlPQwSembbi9m\/f97dEwl+IUnHYoow2GJf\n         7ZXQ==\nMIME-Version: 1.0\nX-Received: by 10.129.148.133 with SMTP id l127mr15897304ywg.272.1461382966473;\n Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nReceived: by 10.37.201.132 with HTTP; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)\nDate: Fri, 22 Apr 2016 23:42:46 -0400\nMessage-ID: <CAPWGcBy+Dwrc0OpZBAGeAfkj5PDSMn-7eowKTY+Yjxxy+FYsGQ@mail.gmail.com>\nSubject: pizarra\nFrom: Salvi Pascual <salvi.pascual@gmail.com>\nTo: Apretaste <apretaste@gmail.com>\nContent-Type: multipart\/mixed; boundary=94eb2c07edcc9913d805311ebc57\n\n--94eb2c07edcc9913d805311ebc57\nContent-Type: multipart\/alternative; boundary=94eb2c07edcc9913d505311ebc55\n\n--94eb2c07edcc9913d505311ebc55\nContent-Type: text\/plain; charset=UTF-8\n\nManda un email sin asunto ni cuerpo a apretaste@gmail.com para usar\nApretaste!com\n\n--94eb2c07edcc9913d505311ebc55\nContent-Type: text\/html; charset=UTF-8\nContent-Transfer-Encoding: quoted-printable\n\n<div dir=3D\"ltr\"><br clear=3D\"all\"><div><div class=3D\"gmail_signature\"><div=\n dir=3D\"ltr\">Manda un email sin asunto ni cuerpo a=C2=A0<a href=3D\"mailto:a=\npretaste@gmail.com\" target=3D\"_blank\">apretaste@gmail.com<\/a>=C2=A0para usa=\nr Apretaste!com<br><\/div><\/div><\/div>\n<\/div>\n\n--94eb2c07edcc9913d505311ebc55--\n--94eb2c07edcc9913d805311ebc57\nContent-Type: image\/png; name=\"guitar_tracks.png\"\nContent-Disposition: attachment; filename=\"guitar_tracks.png\"\nContent-Transfer-Encoding: base64\nX-Attachment-Id: f_inclolab0\n\niVBORw0KGgoAAAANSUhEUgAAAcMAAAG4CAIAAAC2ARuLAAAAA3NCSVQICAjb4U\/gAAAAGXRFWHRT\nb2Z0d2FyZQBnbm9tZS1zY3JlZW5zaG907wO\/PgAAIABJREFUeJzt3X9sU2eaL\/Cn9\/poxyFy0DVc\nW0uskdOQaOVcyEIiNWkTdpI\/inTLrBK1bhVWN6lKiWgHNNDdNeowO+qdboXvVemooKWojAR\/gFTv\nKrkSIxWmdXRxKKmU0E1617o3IbU1TbqyFzxqIkhmZY98\/zhweuqfx37P6\/Oe4+9HVWTcN4fD168f\nn1+PzxPZbJYAAIDBfzB6BQAATA+VFACAFSopAAArVFIAAFaopAAArFBJAQBYoZICALBCJQUAYIVK\nCgDAymb0ChSwtng9FArPr65niOwuX79\/dLijyeiVMpt0YjZ0ZXJ2KbVJZHN4u5876N\/naSCijdnT\nxy85jvx105XTkXVluL25a2hsbJ9HMm6NTSl9f3bi8uSMHLPT2zs05u92I8QiNmLhK1euP35n795\/\ncGx\/e0OZ30mvfPTm29HBUwfm374YV\/8P2+4Tv3q16dovfnHDfui9n3eXWw5vwlXS9MpE8MyNlHP3\n0GivW0rMXpu8cTaYPvXWi3iTV2Bt9vwvL0bJ2+8f8jk3FsOTU1dPrze8c6y7KZ2IJsjZ47KtEpGz\n\/8ihHgelUzNXLkauXvF1nuzFR1YFNv7l0tsX59LNPf5DnXLMF99NO94Za8dcLSBx\/d3g5KrD9+xo\nf4uUmL02OXnmffvfn9y3reRvbazE1+0er4PmiWzeoVefa5ZrliRt80i0XvJ3a0m0SpqOXQunqNl\/\n4rXBbUREnW3etvnNFiemZiXuz12LZuxdJ44dbG8gok5fe0tkxeFtIKKNlZVNe4uniVaJyNbc3NKy\njdKORJMtkmpoQMoVWYv+Zm7T5jvyxlinEnPM4TR6tcSUXgmHV8n57BtHht0SKe\/sBrof\/rufhWhg\noGlmamnTsXtktGXu8uTSusM3Eji2b1s6FU2Qs1\/ezpecXl+H+mMqvU5ElF6aPH0lEt+0eweOHHux\n7FYuH6IdJ11bTWTI0dambBo1te\/b1+kxetPdXNKpxRSRu1uJTXJ3D3a3b5OI0ologty+R59Myas\/\nGx8fH\/\/Jzy4vST7\/iA8xVyK9vpQicu5uVsfc274Nn0eFpBMr62T3+pSNosfvbMkmESWj0nMnj3TZ\n1xeuXk09+0ZgwLke\/U14JU1r8ZVNR8t3BSBdYNHJmH3o1KnR3RSfuhxJ1Oafk0e0bVK1jfnTx8\/L\nh0Z8R88d68AMrUya6NFhpql1IiLn0FvvdMZXM47d7kcT0zlw9FBvk5ReW4mELl8NXnFjx7QiNiKi\njNFrYRISEaULFUIiImdnb7vbsemmuURbT7vbIzXbpqKpDUqvRVPyJ3+CiDbnzhyfe\/QbNt\/RXx1r\nJyKi5sHB9m1N6f62KwsL0cTGfrcRWwSiVdKmZo+d5qLRFHncDW2HTp1aT1x\/\/+Jc+V+E70jOdict\nrMzGNro7Gtz73zjVu7Z06Uxok2gjEU3Z3O1OiVJERDa3u8WzjcjjcSSvz4Si84l0O45HayU52900\ntTQfXxvc1kRElF4JX7lt6z+wrwUb93kkp9dJ8fh3U2xt\/qPJRPtzg14iImqwEZFNIpIkeUdeIiJK\nJ5YSGWfX49Joaxs5NvRogkqOvJmaITKuoom2dy+17O93UXLy3X+YuD0fW00szUSWNslux9u7Etu6\nhnfbMwvn371yc35xJbEyG7m5SmS3Z1PRBDl9yu5oJpGIraysxBZv37iZJHK3OJBzBRp8Q\/0OWrr4\n7qXw7Pz8zY\/efzc0M7O0YfRqiUnyDD7bZlufevf9j27Oz8+GP3z3\/NTMbCpTcsalE0spm7vtu0PP\nmfRjG6nE\/UdbuKvhmdj9xGwkliGXz5ANUhJvm5Qkz\/DJE01XQtdvXF64QWR3en1DR4cGsdNZkabO\nV08eCl2ZvH31fITI5nC194+ODfU2zP9y3eb2KgehU1Nng1NERGR3tg0cOdSNM\/cVkVoOnjpqvxyK\nhC7OENmcbf2HRv0d2CAtbNu+YyfpypXfRK6enyKyu3b7A2ODblpbLPobmWQ0Qc4DbunRoarMUujs\nGeX\/OgdOnepNp4lcLYkrb\/9iddPe9uxYf+krAfh5At+ZDwDASLS9ewAA80ElBQBghUoKAMAKlRQA\ngBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAFSopAAArVFIAAFaopAAA\nrFBJAQBYCfed+bJ4fCUyffP2Z9N3l5d3trb2Pt3X37fP6\/UYvV7mhlTBLEw3V0X8zvx4fOXSpV9P\n35pWP9n3TN\/Y2CsiRyk4pApmYca5WnibdHx8XH5w4cKFGq7MI5Hpm3KIU+Gw\/MzA4OD0remWJ1u9\n3r+q\/froBanyYGyq4rh3714oNPHgwbeNjVv9\/uHt27ezLE2cuTowOCg\/KfhcLVBJx8fHlfjUj2vm\n9mePPovkEJV3\/u3Ppkf\/m6A5loVUeTA8VXGEQhNP9fTs3bPrzhdfhkITr78+XvWiDE9VmasypZ6K\nPFfLbJMWFAwG+azMI3eXl5XHyhtefp7rXx0IBPgtnIRJVR0pIVVzyk\/1wYNv9+7ZRUR79+z69JOP\nGZcvyFzNf17YuVq4kmr5FDpx\/LiWv2Dp7ldtO5+saGQsFlOiHBgcVN75O1tb5b+0imWWdea997QM\nYyFUqsoApKrgkUDNUm1s3Hrniy\/lbdLGxq1allOCOHNV\/cEv8lwtUEkvXLig\/kSq\/bZ979N9co45\nW0+9T\/fVeE10JE6q9DhYuZ4iVWvw+4dDoYlPP\/lYPk7KsijDU1Xmas6RKJHnavXbpPz09+2LfbU8\nfWtavenU90xff98+A9eKHVLloZ6rp9r27dtZjo3mEGSuyn+UZ6zgc1XE60mbd7jGxl5pebI152qy\n5h0uo1fNxJAqmIUZ56qIlZSImne4Rl7yj7zkN3pFLAWpglmYbq6iWxQAgJWg26QAPKx+k8zvQRR5\nn9EUkCoVuzJfeVz2wHM6k5FsthI\/TTGyiuAqhVR5qCjV1W+S6h7Eu8vLd5eXY18tj4290rzDhVQV\nSLUKZc7dl+1wkFei9E\/xRxajbwceUpUZlWpOv6x8RljuQRx5yW\/2VPVVdaqk6kKuq1RxnLQUuQMv\nEAg81dMTCk0YvToWYVSqOf2y+c9DFdSpqoOtt1Sr\/waTGrSvGI5TBx5SNSTVEj2I9RB7RapINb8L\nua5SrX7vHh14lUKqMqNSVfcgqnHtQRSwr1GL6lKtWReygKnqsHcvH9bN\/2nekQq\/f\/jzmZlgMPj5\nzAxjB16lzJKViVJVeg3lrafSPYimS9Uo6vSmwuG6TZX1+0mVM1\/5P3NOipllpJq+HXhIVWZUqiX6\nZYXKqrpU9YVUK1Xg9ytquVWvSulrCwwfWWxYbaYmUuWholSL9SC6XE6kqoZUq1D9InI2jEtsOYsz\nssQwQYiTlfaRJkrV5XK+8PzwC8\/nHlJAqlVAqmqsx0mLbTDnl3mzjBSBWbJCqkjVLFnxTpW1khar\n8fmV3iwjRWCWrJAqUjVLVrxTLVxJxx8r+\/tm+ZwR4XMeqfKAVHlAqpUqsJSK7odlls+Z6j6RdOxr\nRKoKpIpURR6ppj3VMnv3df6F5Jz6GpEqUtUdUuVBe6pFt2zLXlB29uxZllU0BX37GgmpEhFS5QOp\n8qA91aKVVI6vxIGSo0ePalmVePx3Xu8PxR9ZcFro29dISJWIkCrzSKQqYKq1O4ZtRjrerxEUSJUH\npMqD9lQLVFL1PVrr\/CiJjn2NSFWBVHlAqjxoT7XwNqn2+KRyX1Ut7EjlSY3\/UnZIlQekygNSrRSv\nK\/Mp70It0UZSuQsgDCRaVkgVqRYjWlZGpcrlO\/Plf4OWtTRqpJJyLa92ZoRUeUCqPNRhqlxeG+2V\n3qiR4n\/U50OqPBRb1WQylX+\/TJfLybLM6kaKn6o4WWkfqXuqhXuclMd1frxZR0iVB06pJpOpYvfL\n1FggTK2iVOs8K0WZM05le8Wszai7YFqb+KmWuF9m\/nfHWZIud2ytk6xkuLdoKbi3KA\/ip4r7ZWqH\nO7bKqj9Oil4xHpAqD5WmWuJ+mfXwAmkkR1Hijq11lVX1lRS9YjwgVR4qTbXE\/TLlRQmYau3JUZS4\nY6sIWdUsVezdl2LgvUUtTPxUK7pfZp2r6I6tFlZ4mxS9YjKj7oJpbeKnWuJ+mXr9FYLT5d6iHNdP\nPIX77mu\/HpaHVHnglKrL5Sx2v0wef51oKkq1zrNSmKZrAqCWit0vE\/IhK8JxUgAAdjXdJmXpKoNi\nkKqxkD9QLc84oasMqfJg7Hk8q+aPs6OVKrB3L\/eHybTcppWIHj58mPMgn7qrTLlUYvrWdGT6ZtXL\n5DdSd0iVB06pah+Zk7\/8EtQ4f90ZnqoII2XBYLDg43z6HCfdsmVLzoN8lXaVaVkmv5EiQKo86Pvv\nqrSvFKmaZaQsEAjIBTQYDAYCgRIjy+zdl1B1B17+84L0bPCGVHngkap26vzVraVmz9\/YVIUiF9PS\nZZS0fBdUsd9k6cBTE6SrrAbTAqnywCNV7SNz+kqVYso1f8unasjIYqnKZbRsMa3dVVDoKuMBqRpL\nnb96mxT5W4NSQJXd\/GJqdxUUusp4QKrGQv7Wpt4OrXibVH3dg5ZrIPJPhxU8LyZ3lY2OvryztZWI\ndra2jo6+XOxiEY3L5DSSB6TKA6dUtY80PH8eDE\/V8JFV0GGbNP90WLHzYtq7yrQvk8dIESBVHnj8\nu4zNXwTGzitBUuV4nFR7vTd2pLkgVR6QKg91lSprJS2xwZxT7ysamUym\/vGfJo4fP\/7cgQPHjx\/\/\nx3+aSCZTLMssO14onFLVfWTZ8UJBqjwgVVmZe4tSuQMlPDatHzz4g8YOvEo31w3caTI8VX47QUhV\n35HVjdcRUq1CmetJDcHjzo4PHz40\/EiT9VqYq0tVfWmelmueS0OqMqRaGu9URfxWPR53djS8jFpS\ndalq78CrT0iVB96pVv9dUDXowLPYnR2NTVUoGjvwtECqCqTKg9ZUs3kOHz6c\/2QtHT58+EcDAz8a\nGMhms\/ID+T\/DV4yFqVded6dPn1Z+skCqakiVB42pirh3jzs7Wpv2DjzQDqnyoD3VJ7LZbM5TFZ25\n4yEeX1Gfu5f1PdM3NvaK1+up8croxfBULQmp8oBUq1CgkoogHl\/Jv6ODecsoAFiboJUUAMBERDxO\nCgBgLqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGS\nAgCwQiUFAGCFSgoAwAqVFACAFSopAAArVFIAAFaopAAArFBJAQBYoZICALCyGb0CROnEbOjK5OxS\napPI5vB2P3fQv8\/TQEQbs6ePX3Ic+eumK6cj6\/JYm6PZNzh0cH9Hk6GrLLz74b\/7WSip\/NHu8u0f\nO7S\/pYEovfgPPz2zPvY\/TnY3EW3Mv\/8356MZe9eJ\/\/lqu2Tg+prLxuzp4xfj6mdsu0+80x3624ur\n3z3lfPatd4bdtV41E0vHLv1NcGaT2g6990Z3g9FrUzHDK+na7PlfXoySt98\/5HNuLIYnp66eXm94\n51h3UzoRTZCzx2VbJSJHz6HRLml9dfb65OTZeOrEOwfxzi\/H3jV6ZNAtpdfnJz+8MXkp0vXf92+j\nVHw149jtbiAi2lgKL2bITpvRSCzdjkArYvMOvfpcs\/z+kaRtnib72NGja0REmWTkUijqbnEYun5m\nk46F5zfJbqOlSHStu9t0m0pGV9L7c9eiGXvXiWMH2xuIqNPX3hJZcXgbiGhjZWXT3uJpolUisnu9\nHR3bqKPT51x\/82Jkcv65k+YLu8Ykh6elxSPR2oZTuhFvcEhEtJGIpmwen1Mioo1oeCnjHDjYNnd5\nNryYbu9AKa2A5PT6Or736dPQ0eQhovs3fxPdbB4e6TTfdpWB0rFIdNPeNdofv3wjvLTW3d1E6cUP\nf3om2j7gux+ZS1LzwKv7NycuzSTJ1X\/spFwuRGLwcdJ0ajFF5O72PM5FcncPdrdvk4jSiWiC3PJ7\nXqWprctNlFpKpWu+smazPvX2T8bHx8f\/9uxc2jt0sLuJKJ2KJsjZ5m4gorVoZIkcu3s7+9vsmWhk\nccPo9TWdQlNwYz4Uijf0H+zfVvPVMTO5kPr6O3u7nBQPR9eIiCSSaDOW6nztpL85szr1Ydjz6s9H\nfZSMTEbFm6xGb5PK0kRE6ZWP3nx7ap2IyDn01judqt3Q749NExFh+6kse9ehY\/vdUnojMRu6NHn6\nQ\/c7r3lXYut2j7eJiNaWwktk7+l2N7h72mxz0anYRkeHaB\/0AtucO3N87tFjm+\/or451SESUiEws\nZJpHnm3B\/KxEejE8v2lr7\/Y0OBt8jhuRSHStt1eejJ5+n9udanPQqqO\/y+NO+5wU3VjfIBJsrhpc\nSSVnu5MWVmZjG90dDe79b5zqXVu6dCa0+Wg31N3ulCj1vd9YW5pJErXlbatCHsnh9ng8EpHHfSA8\ndX5pNrFhj6bI2e+WiNai4TgRzQR\/MiOPXpxa2ujAHqlmtraRY0MeeRZKDvlBeuVmOEne0U5skFZk\nYzESzVAmev74uPzEeji61ttNRGSTSCKSpMc\/jK5YRRm9Xtu6hndfO79w\/t0r\/gO7nbS+NHtzlajZ\nnk1FE+QcbG4guZJmVuOLi4n11blroTg5B4Z8eMuXlV5PrKyQlN5IzF5fIXK2ODdWVzYd7Z4GorVo\nJE4238ix5zwSUToeej+0GFna6EQp1S6TTj\/av0+nEvebPNuktaXoOjkHW3AEvyIbsalohrxDR\/1t\nDUTpxPXzlxfC82vdHqNXrBJGV1Jq6nz15KHQlcnbV89HiGwOV3v\/6NhQb8P8L9dtbq8yJVORi2ci\nRGR3+Z49OjqMnScNNucuBuX9T5vD2zM61t+QOJ8ip88tyVuk9p7netvlJN3P+SbORsMopdpllkJn\nzyh\/cg6ceutF93psncjtxkn7imwsTS1mbL79\/R0tDUREngPdkwuRyPz9g0avWSWeyGazRq8DAIC5\noccJAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABW\nqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwMrw78wvLB5fiUzfvP3Z9N3l5Z2trb1P9\/X37fN6TXU7\nglpBVgCGE\/E78+PxlUuXfj19a1r9ZN8zfWNjr6BA5EBWACIovE06Pv7oHn8XLlyo4co8Epm+KZeG\nqXCYiAYGB4lo+tZ0y5OtXu9f1X599MIj1ZysiGhgcNACWWln7FwVx71790KhiQcPvm1s3Or3D2\/f\nvp1laUhVVkGq2TyHDx8u+LhmDh8+\/KOBAfm\/bDarPDZkZfTCKVV1Vuq4TJ2VdobPVXGcO\/fB3J2F\nbDY7d2fh3LkPWBaFVBXaUy2zTVpQMBhkrPSl3V1eLvY81786EAjwWzjxSVWdlbJZSvyz0s6MqYov\nP9UHD77du2cXEe3ds+vTTz5mXD5SlWlPtXAl1bJJf+L4cS0rt3T3q7adT1Y0MhaLFSymO1tb5b+0\nimWWdea997QMY8EjVXVWA4ODSjHlmpXlUxV8ZMFUGxu33vniy717dt354svGxq1allMCUpVpT7VA\nJb1w4YL6E6n2B0p6n+6Tq4NcF6bCYflQae\/TfTVeEx1xSjUnK\/XzuixfcIbPVXH4\/cOh0MSnn3ws\nH9FjWRRSVWhPtfptUn76+\/bFvlqevjUtF1BZ3zN9\/X37DFwrdjxStWpW2tXz+1xt+\/btr79eape8\nIkhVpj1VEa8nbd7hGht7peXJ1pxrJJt3uIxeNeEgKwARiFhJiah5h2vkJf\/IS36jV8QEkBWA4dAt\nCgDAStBtUgAwi9Vvkvn9yvV2fKlAJa3otF06k5FsthI\/TTGyiuAqhVR5QKo8VJTq6jdJdb\/y3eXl\nu8vLsa+Wx8Zead7hqp9Uy5y7Hx8fLx2lvBKlf4o\/shh9O\/CQqgypWinVEr3dIy\/5zZ6qdjhOWkoo\nNPFUT08gEHiqpycUmjB6dSwCqfJgVKq3P3u0Naq+Dk\/9fJ2o\/htMatC+YjhOHXhIFanqzqhUS\/R2\n10Psiur37tErVimkKkOqjCOFStWQ3m4BO5t12LuXD+vm\/zTvSIXfP\/z5zEwwGPx8ZoaxA69SZskK\nqdZ5qkpfstLbnfO8mulS1Y71+0mVM1\/5P3NOipllpJq+HXhIVYZU9RqpZlSqJfqVhcqqulS1K\/D7\nFbXcqlel9LUFho8sNkyvk3elIVUekCoPFaVarF\/Z5XLWVarVLyJnw7jElrM4I0sME4Q4WWkfiVR5\njDRRqi6X84Xnh194PveQQl2lynqctNgGc36ZN8tIEZglK6SKVM2SFe9UWZdSrMbnV3qzjBRBRf+u\nZDKV36vncjlZlqn7SBGYJSvTpVriZ85IU+RfncKVVPvx5hJHH3JW0Swj+eGRajKZKtarlzNBkapZ\nsjJXqpirsgKVVH0FWdleMbN8elf3iaRjBx6nVEvcWzTnuJU4qeqoolRL9DUKlZW5UsVclZU5Tlrn\nX53NqQNPx1TVvXrqy1DqrVePNKSKvsYqYK5qVPQ4adnN+7Nnz3JZI5Ho24FHHFJV2kty7uN0d3m5\nHl4gmcZUS\/Q11k9W2vGbq5R3H1wL5F+0ksrxlbhZ69GjR7X8BfH477zeH4o\/suBrqW8HHnFINefe\nosqAna2t8qIETFV3GlMt0dcoQlYmTbWsYnNVfR9cEfJnTBXfBVWKgX2NGql78qbC4dK9enWuor5G\n0J06f\/U2qTXyL7BNqr5Ha50fJ9WxA49TqnV+b9GKUq3zrLTDXK1C4b177fFpv6pAtJHKkxr\/pex4\npFqiV6\/qZVo1VdGyskaqmKsyXlfmU95aijaS9LsAQncV\/buK9eoh1RyiZWWZVAv+pDqbq1yOk0qP\nv1ZA2JHS4xYxScgOvIKQKg9IlYc6TJXLa6O90hs1UvyPeo19dQUhVe0wV3kotqrizGrdUy3c46Q8\nrvMzTjqqKFXtfXV1DnOVB06pWntWlznjVLZXzNoEvF9j\/jGmOoe5KjNqrmqnvVvUjHA9aSm4XyOY\nhfh3bK3TbtGyLNDgVZbu3aJloa+Rh3oIzai5qp21O5urr6ToFuUBfY08YK7ygM5mNezdl2KK+zUC\nEDqbjcb6Tc\/WJuD9GvVaGcvAXJUZNVe1s\/asLtx3X\/v1sLyKUnW5nBr76uoc5ioPnFK19qw2TddE\nvSnWVwdgXhae1ThOCgDACtukAAWw9DVCHcIZp9pBqjzwSNXafY1aYK5WivXeorKHDx9u2bJF\/cDs\nI2XBYDAQCOQ\/rgJSVRh1x1bta1tpX6MIqeoIc1Whfa7qc5xUWaeyK2eWkbJAIBAMBom5jFbHLFlV\nmqqxfY1a1rbSvkYRUjWWWWYgv7laZu++BEHaV3iTi6kuZRSpyvTta+SRqrX7GrXAXJVpn6vlvwuq\n2G\/WQwcePd4a1aWYIlWZvn2NPFK1dl+jFpirMu1zFVdBlaIUUGU3H9ihrxHMQvtcxVVQpai3Q2t\/\nnNSq9O1r5MHafY2gnfa5WmCbVH2qTuNpu2IPTDqSB6TKA6dU5b7G0dGXd7a2EtHO1tbR0ZeLXQKF\nVM0yA7mmqsM2af7psGLnxcwyUgRmycqqqWrva0SqZpmBXFPleJxUe703dqS5IFUekCoPdZUqayUt\nscGcU++NHVl2vFCQKg9IlQekKitzb1Eqd6DEXJvrBu40IVUekCoPSLUKZa4ntYyHVbXc6dgtSkj1\nMaRaWnWpcrq3qGXwnqv1cj1pdZ88xnaLig+p8lBdquLfW9RYvOdq9d8FVQ+9YsSnWxSpIlXdcerB\nrfNUSftczeY5fPhw\/pN16\/Tp08pPFkhVDanq7ty5D+buLGSz2bk7C+fOfcCyKKSqpnGu1svefXXQ\nLcoDUuVB\/B5cM9I+V5\/IZrM5T1V05g40Qqo8IFUekGoVClRSAACoCPbuAQBYoZICALBCJQUAYIVK\nCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RS\nAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAlc2ov3jt5i\/\/9uoqOQZOvfOiRyo\/Pr3y\n0ZtvRwf\/\/pj0\/s9CyUdP2l2+nuGR4c5tGhZQb+6H\/+67oIiIyNF\/6p2DWrIGbdKL\/\/DTMwsZ+Q82\np7dz0H9wsKXB2JUyq++FSWRzeLv9hw52N61ceTMYWf9uXNvRc290CDiJjaqk9+cjq2Sz0\/rc7cSw\nllK6sRJft3u8TbRKRPbdI4f6nZvJuesTU+ffTh1567XOJv7rbEJyUPJjqcHtFnAGmp7r2SP+Nlpf\nikzeCAVX1k79fBifVlVzDZ0Ya5NoYyV8+erMpatdncfaBw8d3Z0mItqMhi5OZbzbjF7HwgyqpPcX\nIqvUPDTUdO3qXCQxfNAjydtQNDDQNDO1tOnYPTLaMnd5cmnd4RsJHNu3LZ2KJsjZ75ZolYgkZ0t7\nh0fq6PC50m+enZuYud+5X9CAjfUoKOXP6ZWP3nx7yjnQTzOReNrVMzZsD1+aim86u44EXsWnUVUa\nWto6Ohqoo7Ot6Zc\/C4WvLR54TcRtJnNocHtaWhoo3RB30Fza0UAkuds73ESUjl25nLT3nNgv6OaA\nMcdJ7y\/cXCVXb1dnf7ttfT6SSBORZJOIklHpuZNHuuzrC1evpp59IzDgXI\/+JrySprX4yqajxZO7\n59TQ0u0hSi0l0kb8M0xIkiSilbj74BujPkrOXJyQ\/CePdtlTcxNz941eN7Nr8vmclFldTRm9IiYW\nP398fHx8\/Ce\/CK06u8aGWh5XzXTi2qXIept\/qF3UgyeGbJPeX7iZJOeQr6nJ3uOh6Hx4xT\/WQkRE\nzs7edrdj001zibaedrdHarZNRVMblF6Lpsjtc0q0UWiBhh3tFd361Ns\/mXr02NF\/6h2\/RETk7uny\neNK7nRTNdPa2uB3rbppL3d9ME4nKx4xGAAAVOUlEQVT5cW8W6TQR2TAbGTT7T4y1NaTTa\/Hrl0Pn\n33U+OlaysTgRTtp7DnaLu9tkxKt+f+5mkogmf\/GTSfmJ+chKukU+mtdgIyKb9GjjiUj+mU4sJTLO\nLncD5VTSjdjtFSK3T9AtfsPZu0aPDD4KR3K6JZK3lxrkbImoSZkAmQK\/DpVIRaMpsnd5xX23i09y\nejyeBiKP50DX9YWp+djasGcbrUVvLGTsPf0tAr\/NDaikiblIkhw9h470O4loY\/7S2RvzkZX0UIlf\nSSeWUjZ3m1P5cyq2uLiWSSxcDy1s2ruGunGQtIh0Op1+dOQjnUqsSQJPRbPaiMUW7ZSKR34zmaTm\noQPtyLh66dTKykoDpddi4bl1sre5m4gonZiNka29S+gzebWvpInbkRQ5n93f3eImIiLnfu+Ny\/Ph\n2IG2or+SSUYT5Dzw3Ybn5sLVswtEZHO2DRwZHe4Q9diJ4R4HJXON\/P1rBq6MRSVvnD1zg4jszV3+\nwMFBt9HrY2qroTNvy4\/sLt\/QoYPtEhFtJFIZanCJva3\/RDabNXodAADMDT1OAACsUEkBAFihkgIA\nsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACA\nFSopAAArVFIAAFa4exdAAfH4SmT65u3Ppu8uL+9sbe19uq+\/b5\/X6zF6vczNwqniO\/MBcsXjK5cu\n\/Xr61rT6yb5n+sbGXrHG294Q1k618Dbp+Pi4\/ODChQs1XBnh3Lt3LxSaePDg28bGrX7\/8Pbt21mW\nhlRl4qcamb4pv+GnwmH5mYHBwelb0y1Ptnq9f6XX3yIy3qkODA7KT1on1Wyew4cPF3xch86d+2Du\nzkI2m527s3Du3Acsi0KqCvFTPXz48I8GBpT\/stms\/KBOXjjeqWazWeulWmabtKBgMMinqhssEAjk\nPPPgwbd79+wior17dn36yceMy0eqMvFTvbu8LD9QtkmV5wV5mfJT1RfXVPOft0CqhSuplk36E8eP\na\/kLlu5+1bbzSfFHnnnvvfwnGxu33vniy717dt354svGxq1allMCUpWJn2osFlPe9sp+KBHtbG2V\nFyVgqvrinar6I8oaqRaopBcuXFB\/ItXzQT2\/fzgUmvj0k4\/lI3osi0KqCvFT7X26L+c9L9fT3qf7\ndFm+4HinKoepFFNrpFr9Nmk92L59++uvl9rNqQhSlYmfan\/fvthXy9O3ptUbpH3P9PX37dP97xIT\n11TlP8rZWiZVXE8KkKt5h2ts7JWWJ1tzrnxs3uEyetVMzNqpopICFNC8wzXykn\/kJb\/RK2IpFk4V\n3aIAAKx02CZd\/SaZ3wFmjS12gLIw\/4EKVtKKTtutfpNUd4DdXV6+u7wc+2p5bOyV5h2udCajjExn\nMpLNVuKngSMrDK0aFaUqclZIVT2y9vOfMTEtDE\/VjHO1zLn78fHx0lHm9NXJ5+PkDrCRl\/zqVZQf\nl\/5p1Mja0J6qyFmxp6pvt6ixqZboK+U0\/2sDc1Wmfa6yHie9\/dn3rmnIfx4gRyg08VRPTyAQeKqn\nJxSaMHp1mKjnv\/otgPlvDdrnavXfYCK3BJToAKtBJ4a5aE\/V2jh1ixqSqnr+q\/t2zD7\/MVdl2udq\n9Xv3coOXugNMTZAOMDE78MqmWpapU+XULWpIqjl9pUox5Tr\/MVcFnKuse\/dKp5c8h0p3gMkHgPN\/\nijZSBGbJqopU\/f7hz2dmgsHg5zMzjN2ildL936We\/+pt0prNfxGYZQZynaus309aoq8u56SY8sf8\nn0KN5Ed7qmbJqrpU9e0WNTZVY+c\/P5irMu1ztcDvV9RyW6wDzOVy5qyceqVLX4XAaWSxYbWZmhWl\nanhW2kfWeaqc5n+dp2rGuVr9IpQNY5fL+cLzwy88n7vpqwzI2YQusY3Ne2SJYYIQJyvtI+s8VU7z\nv85T5TSSX6qsx0mLbTDnl3mzjBSBWbJCqkjVLFnxTpV1KcVqfH6lT2cyyWQqv6\/O5XKyLFP3kSIw\nNgGkirmqHVKVFa6k2o83lzj6kLOKyWSqWF9dTpTal8ljJD88UjXLSH4wV3lAqpUqUEnVV5CV7RXT\nXulL9NXlHGMy4ydSWZxSNctINR27RWszV9U90JirOVABZGWOk+r41dnoq1PU+Zfnc+oW5TRXCz5f\nP1ABNCp6nLTs5v3Zs2cr+ptK3K+x0kWZl+6pmpG+3aLEc67mP18PL5CMa6o5nbUWSLVoJZXjK3Gz\n1qNHj2r5C+Lx33m9P6SS92uUF6WM1L5MHUfW5rXUPVXBRxZMVd9uUeI8V9UwV9UYK4C6s9YCqdbu\nO\/PV\/XPq1jpr3FkQtDOwW1SjinqgQaOKOmtNp8A2qfoerToeJanz+zVyStWMdOwWxVzlAalWofDe\nvfb4tF9VUKKvruplsoxUntT4L2XHI1XRRpIlUsVcRaqV4nVlPuWtZTqTKdZXlz9S+zKrHkn6XQCh\nu9okgFQxV9khVRmX46TS468VEHak9LhFTBKyA68gpMoDUuWhDlPl8tpor\/RGjRT\/oz5fsVXV2IFX\n0TKrG2mlVMUZKX6q4sxA7SN1T7Vwj5PyuM7PjeiIU6raO\/AsCXOVh4pSrfMZqChzxqlsr5i1GXUX\nTO20d+BZFeaqzKi5qr2z1tpqdz2pGYl\/F0xrd+CBdkbNVXTWyqo\/TmqBBq+ydO9rLAs9uDzUQxRG\nzVV01sqqr6Toa+QBPbg8YK7yIKcqeGetBbtFzchEfY2EHtz6ZtRcRWetjPWbnq3NqLtgamftDjwt\nMFdlRs1VzEBZ4b772q+H5XFK1eVyauzAsyTMVR4qSrXOZ6DCNF0TUEyxDjyA2sAMJBwnBQBgh21S\n02Pp1QOoJQvPVZxxqh0eqaJXD3OVB8zVShXYu5f7w2Ql7j2g9vDhw5wHZh8pCwaDBR9XgVOq6l49\n5QKU6VvTkembVS+T30jdYa4qTDdX5elqmbmqz3HSLVu25Dww+0hZIBCQJ2UwGAwEAlp+RUda1rbS\nXj0RUjWWWWagteeqls5mEVLVrszefQmCtK\/wJk9QXaYmj1TRq4e5qjDLXLVkZ3P574Iq9pv10IFH\njz\/hdZmgPFIVvFevBu8QzFWFieaq9TqbcRVUKcqkVHadRINePZCZaK6SFTubcRVUKerP9tofe9IC\nvXogw1w1VoFtUvV1D1qugcg\/HVbsvJhZRvLAKVW5V2909OWdra1EtLO1dXT05WKXlSBVs8xAS6Zq\n7bmqwzZp\/umwYufFzDJSBNrXVnuvHlI1ywy0aqoWnqscj5Nqr\/fGjjQXpMoDUuWhrlJlraQlNphz\n6r2xI8uOFwpS5QGp8oBUZWXuLUrlDpSYayfIwJ0mpMoDUuUBqVahzPWklvHw4cMqIlNfmsd+mR5S\nlXG6C6ZlYK7ywDvVermetLpPHmM78MRXXari37HVWJirPPBOtfrvgrJAg5cWPDrw6jxVfe+CiVQV\nmKs8aE01m+fw4cP5T9at06dPKz9ZIFXFuXMfzN1ZyGazc3cWzp37gGVRSFUNc5UHjanWy959dcTv\nwDMj8e\/YakaYqzxoT\/WJbDab81RFZ+5AI6TKA1LlAalWoUAlBQCAimDvHgCAFSopAAArVFIAAFao\npAAArFBJAQBYoZICALBCJQUAYIVKCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIl\nBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWNmMXoE8G7Onj1+Mq5+x7T7xq9faJaNW\nyLTSKzevXLkxG09liOxOX8\/I6IsdTUavFIAliVdJiYjI5h169blmeeUkaZsHZbRia7ffP311SWob\nGBlqb1iPhiemzr4rnfr5MLIE0J+glVRyen0d2AxlcH\/u+lLG3nPs2IvtEhF1+tq8Mwm3I7344U\/P\nRNsHfPcjc0lqHnh1\/+bEpZkkufqPnTzY3mD0WgOYlLjHSdNGr4CppVOLKSJ3p7IF2uDpHexuaZIk\nkmgzlup87aS\/ObM69WHY8+rPR32UjExGNwxdYwAzE3SbdHPuzPG5R49tvqO\/OtaB7VMdefp9bneq\nzUGrjv4ujzvtc1J0Y32DCBulAFURtJLa2kaODT3anpIcOLRXMcnZ7qaFxGxso7OjgYhoY3EiNO8Z\nPOAkIptEEpEkPf4h6iwAMA1h9+4z6cc2Uon72NWv1LauA7vtm3Pn379yc3Z+9uaVd9+\/MbMQQ44A\nXAi6NZJZCp09o\/zJOXDqrRexYVqZps5XTx0KXZ6cvXoxQmR3+Z49OjrcIsXCRq8YgAU9kc1mjV4H\nAABzE3bvHgDANFBJAQBYoZICALBCJQUAYIVKCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFih\nkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKwEvfsIyAYHB+UH4TDuGgIgrsKVdHx8\nXH5w4cKFGq6McO7duxcKTTx48G1j41a\/f3j79u0sS0OqAFZVoJKOj48rb3X14zoUCk081dOzd8+u\nO198GQpNvP76eNWLQqoAFlZmm7SgYDDIZ2UMFggEcp558ODbvXt2EdHePbs+\/eRjxuWzpGrezPNT\nBbCewpVUyxbTiePHtfwFS3e\/atv5pPgjz7z3Xv6TjY1b73zxpbxN2ti4VctySqgi1d\/+9rcFnzd1\nqgDWU6CSXrhwQb31VM\/7oX7\/cCg08eknH8vHSVkWhVQBLKz6bdJ6sH37dpZjozmQKoBV4XpSAABW\nqKQAAKxQSQEAWKHHyfRWv0lGpm\/e\/mz67vLyztbW3qf7+vv2Ne9wGb1eAHWk8JX5yuOyJ0nSmYxk\ns5X4aYqRVQRXqepSzX8m5\/nVb5KXLv16+ta0\/Me7y8t3l5djXy2Pjb3SvMNl+VQBBFHm3H3Zbhz5\nDVP6p\/gji9G3W7S6VIutvywyfVMuo1OPG\/MHBgenb023PNk68pJfzFQBrAfHSUuRu0UDgcBTPT2h\n0ITRq1PA7c8ebY0ODA4OPP66E\/XzAFAD1X+DST20r3DqFq0i1WLP311elh9Mff\/Lou4uL9fDCwQg\niOr37tEtWqkqUi3bLRqLxZRiqt4m3dnaKv+KgKkCWI8Oe\/fyKYj8n+YdqfD7hz+fmQkGg5\/PzDB2\ni1Yqf90Krm3v033K46lwWNkyVT+fswTDUwWwHtbvJ1XO0ub\/zDmBa5aRavp2i1aRqlrBte3v2xf7\nann61rR6g7Tvmb7+vn3CpgpgPYW\/wUT776vfNqWvgzF8ZLFhtXnDV5eq8kyxf1fzDtfY2CstT7bm\nXE\/qcjnrIVUAQVQ\/3XN24krs5YkzUvxd0RLrU+zf5XI5X3h++IXncw8+IFWAmmE9Tqr9ikKzjBRB\n6Ss3i41EqgBGYZ3x6UwmmUzldyu6XM78kSV+ijNSBPnrpksCBr5SANbGesYpmUwV61bMeYuWOKaW\n88YzdiQ\/2lPNPyjJ\/u8y9pUCsLYClbSie7fldCvKZ5DlbsWcI3d1vk1aUao8tknVr5Ryor9mrxSA\ntZU5Tlp260ndrVjwechnyJfn57wiypWneKUA2BU9Tlp2V\/Ts2bOk6lbMcXd5WR4AahpT1f68dnil\nAPgpWknlt3qJGwsfPXqUvt+tqLaztVUeEI\/\/zuv9oZZVMXZkbaqJxlQVSrdozvNVJKB+pdRN+lxf\nKdRoqBOsV0EpXYnym7N0tyIYSHlFcr41Cq8UALvCPU7azzKX6FbUcS0toKJUeVBeKfmP8uuFVwpA\nF2W+C6qsEt2KOSO1XytTm5HKkxr\/pey0p1rsKqgSI8smUJtXqsSqAliYDlfmF+tWzHlHlbhWxpCR\nJPDFOsWugiK2BGrwSpHAqQLww+U786W8L+AQbaSyuWeivkakCiAsLjO+2PtNY7diRcusbqQZN6DY\nE+CdvxlTBdBF4R4n5bGO50a0dytaEqdUtavz\/AG4KnPGqWxfo3Yl7oKZf+TOknikqh3yB+CndvcW\nxV0wjYX8Afip\/jhppe0r6lYodY8NuhXVatMtivwB9FV9Jc3pXyymYLfiwOCg8mYWpK9UkGpSm27R\nmuUvSKoAvNXuapXep\/vkd3LOndnRrVgbyB+AH9ZvetYOfaWCdIvWbf4A\/LDeW1Q7l8upsVvRkgyp\nnmp1nj8AVzXtRSnWrQi1gfwBOKndVVAAAFZV021Slm5FAABh1e6ME7oVjT3jBAD8sN5bVPbw4cMt\nW7aoH+SrtFtRyzL5jdRddalqXLixWRmYKoAg9DlOqrx\/SryRKu1W1LJMfiNFoH0ljc3KXKkC8FBm\n776EqrtFc64Mr59uRZZU6yQiAJMq\/11QxX6TsVtUGVA\/3aJVpKpjt6ghI\/EBAHWidldBqbsSp8Jh\n3IUUACyjdldBoVsRAKyqwDap+rRyRaeY8x+oyd2Ko6Mv72xtJaKdra2joy8XuwRK4zI5jeSh6lRL\nPJPzvCFZGZsqgCB02CbNP3Vb7Byu9m5F7cvkMVIE+etWywSsmioAJxz37rVfXWjsSJFdu3Yt5xmk\nCiAg1jNOJXbuct5yxo4sO14oSBXAXMrcW5TKHdQz166lgbuiSBXAwspcT2oZ1e2W3rt3LxSaePDg\n28bGrX7\/8Pbt21nWAanKgsFgIBDIfwxgXvXyrXrVbSWFQhNP9fQEAoGnenpCoQnd18rsqks1EAgE\ng0FCGQULqf67oOqhfeXBg2\/37tlFRHv37Pr0k48Zl4ZUFXIxRRkFy6hm775+3gCNjVvvfPHl3j27\n7nzxZWPjVsalIVWFXEZRTMEy6mXvvjp+\/\/DnMzPBYPDzmRm\/Hzft0IdSQJXdfACzeyKbzeY8VdFZ\nZtAIqQJYWIFKCoJ5EJv9l68f\/qc\/\/4u2JqNXBQAKqul9nKCEzNc3Jv7Z9p8b\/\/Bva86nf\/zUDpvy\n\/M3P6akf\/xe8VADiwnFSYWzZue\/Zwb8YHOzMzH32dUZ5+g+p3\/\/RZmt0On9g4LoBQEnY0BGFzdni\nIiLKPPz3LU7lMs0\/JO\/G19Yyc7M\/aP\/B\/\/lfn2\/p+NO13\/\/Z8HALyiqASFBJBZJJ\/vPNz\/\/vvzq6\nfux8\/Lr8wNXibfoX6ur+s6ZUcssW11P\/1fvvf0AZBRAM9u4FYnP9+eBfjvz4T+cmbiUzxQb9oAmF\nFEA0qKSiePDN1w+IiOhPGv\/kDw+LFlIAEBD27kXxx3\/75\/\/9\/2Ku\/\/jH5O+dfzH4+NR9Zu1fv15b\n++PS1ztbHv7+4cN\/\/3ptZ1sTXjQAweB6UgAAVti7BwBghUoKAMAKlRQAgNX\/B8il\/EMAMFo9AAAA\nAElFTkSuQmCC\n--94eb2c07edcc9913d805311ebc57--","headers":{"Received":["from mail-wm0-f46.google.com (unknown [74.125.82.46]) by relay-1.us-west-2.relay-prod (Postfix) with ESMTPS id 3864EE065E for <webhook@apretaste.net>; Sat, 23 Apr 2016 03:42:50 +0000 (UTC)","by mail-wm0-f46.google.com with SMTP id u206so51783694wme.1 for <webhook@apretaste.net>; Fri, 22 Apr 2016 20:42:50 -0700 (PDT)","by 10.28.5.132 with SMTP id 126csp1069965wmf; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)","from mail-yw0-x229.google.com (mail-yw0-x229.google.com. [2607:f8b0:4002:c05::229]) by mx.google.com with ESMTPS id f185si2764353yba.282.2016.04.22.20.42.46 for <apretaste@gmail.com> (version=TLS1_2 cipher=ECDHE-RSA-AES128-GCM-SHA256 bits=128\/128); Fri, 22 Apr 2016 20:42:46 -0700 (PDT)","by mail-yw0-x229.google.com with SMTP id t10so136409743ywa.0 for <apretaste@gmail.com>; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)","by 10.37.201.132 with HTTP; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)"],"X-Google-Dkim-Signature":"v=1; a=rsa-sha256; c=relaxed\/relaxed; d=1e100.net; s=20130820; h=x-gm-message-state:delivered-to:dkim-signature:mime-version:date :message-id:subject:from:to; bh=N+GjHTHZxxbyh7rKn0TtOIiqsh2+6+mB9OuLkazjFbw=; b=FXpvdm4cg2uq1fp8ltQtHS75PMBMYkjqfqoDJ3lvl3TCKev\/+NwNbxtfcT9BgrYpCL UZ6VQOEQVxwH+XcPsbex4SOTwevl76BPX4xwv1EjEeAPULl17dNcg8fSny1r1efWpumn uPla\/abE8ANA3E9wtyVSh2B8mIW5AKT1\/VDxoYMKg6R5CcS3MYraBFNmgJyopiMir3XY gFSF+2RQC0gEf8CEXekOFHcUBvgw0JQ6AyRQfb2L9rRauRBOKdjmLgB+0qfliIMz\/eUy \/sHI7ztw7csSKTMB0iYADULw2vII3mNtSvhLs8k5tNz7JEgteLfTabsELUnOVMzPVa6s 5V3A==","X-Gm-Message-State":"AOPr4FW6k1zx3b2WUEjv7ejWgGI\/tz6eOyr1N1kuytV9po7LTjfaW0NNRaFR9bOw2AKvb+tXjJ1LY662TWmtTD\/pOIiOFLA=","X-Received":["by 10.28.9.139 with SMTP id 133mr546157wmj.72.1461382969050; Fri, 22 Apr 2016 20:42:49 -0700 (PDT)","by 10.37.10.4 with SMTP id 4mr15263290ybk.160.1461382966762; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)","by 10.129.148.133 with SMTP id l127mr15897304ywg.272.1461382966473; Fri, 22 Apr 2016 20:42:46 -0700 (PDT)"],"X-Forwarded-To":"webhook@apretaste.net","X-Forwarded-For":"apretaste@gmail.com webhook@apretaste.net","Delivered-To":"apretaste@gmail.com","Received-Spf":"pass (google.com: domain of salvi.pascual@gmail.com designates 2607:f8b0:4002:c05::229 as permitted sender) client-ip=2607:f8b0:4002:c05::229;","Authentication-Results":"mx.google.com; dkim=pass header.i=@gmail.com; spf=pass (google.com: domain of salvi.pascual@gmail.com designates 2607:f8b0:4002:c05::229 as permitted sender) smtp.mailfrom=salvi.pascual@gmail.com; dmarc=pass (p=NONE dis=NONE) header.from=gmail.com","Dkim-Signature":"v=1; a=rsa-sha256; c=relaxed\/relaxed; d=gmail.com; s=20120113; h=mime-version:date:message-id:subject:from:to; bh=N+GjHTHZxxbyh7rKn0TtOIiqsh2+6+mB9OuLkazjFbw=; b=ZBeB2ZxjRtgzZ8J1nEk5JD\/Sm6wzeF3iKwqiJKEmMTNFRWMxjTS2InuN3QLgf+55xJ cefPyp2dUN39HhXzNPJ1SHiEbzxHZuAS1vESI+scmxQ3Ve\/mEddJyLjvcWdFNmmnrXtg XMoy72Sy1TtxycaylkMdnh7tj2iuPUn6WaAoFHL100HzXNpMmJy1HA2endeWRiNwU8LT TGFkRTfIxN+385fHw7Yzvyg14tI1gflSI41YI0qGITHPE6DFavCohDSdWu0khGvt0Fdx dkubQVIHlCcZT1meW5lWuziwFrcRxcqCwRTlPQwSembbi9m\/f97dEwl+IUnHYoow2GJf 7ZXQ==","Mime-Version":"1.0","Date":"Fri, 22 Apr 2016 23:42:46 -0400","Message-Id":"<CAPWGcBy+Dwrc0OpZBAGeAfkj5PDSMn-7eowKTY+Yjxxy+FYsGQ@mail.gmail.com>","Subject":"pizarra","From":"Salvi Pascual <salvi.pascual@gmail.com>","To":"Apretaste <apretaste@gmail.com>","Content-Type":"multipart\/mixed; boundary=94eb2c07edcc9913d805311ebc57"},"text":"Manda un email sin asunto ni cuerpo a apretaste@gmail.com para usar\nApretaste!com\n\n","text_flowed":false,"html":"<div dir=\"ltr\"><br clear=\"all\"><div><div class=\"gmail_signature\"><div dir=\"ltr\">Manda un email sin asunto ni cuerpo a\u00a0<a href=\"mailto:apretaste@gmail.com\" target=\"_blank\">apretaste@gmail.com<\/a>\u00a0para usar Apretaste!com<br><\/div><\/div><\/div>\n<\/div>\n\n","attachments":{"guitar_tracks.png":{"name":"guitar_tracks.png","type":"image\/png","content":"iVBORw0KGgoAAAANSUhEUgAAAcMAAAG4CAIAAAC2ARuLAAAAA3NCSVQICAjb4U\/gAAAAGXRFWHRTb2Z0d2FyZQBnbm9tZS1zY3JlZW5zaG907wO\/PgAAIABJREFUeJzt3X9sU2eaL\/Cn9\/poxyFy0DVcW0uskdOQaOVcyEIiNWkTdpI\/inTLrBK1bhVWN6lKiWgHNNDdNeowO+qdboXvVemooKWojAR\/gFTvKrkSIxWmdXRxKKmU0E1617o3IbU1TbqyFzxqIkhmZY98\/zhweuqfx37P6\/Oe4+9HVWTcN4fD168fn1+PzxPZbJYAAIDBfzB6BQAATA+VFACAFSopAAArVFIAAFaopAAArFBJAQBYoZICALBCJQUAYIVKCgDAymb0ChSwtng9FArPr65niOwuX79\/dLijyeiVMpt0YjZ0ZXJ2KbVJZHN4u5876N\/naSCijdnTxy85jvx105XTkXVluL25a2hsbJ9HMm6NTSl9f3bi8uSMHLPT2zs05u92I8QiNmLhK1euP35n795\/cGx\/e0OZ30mvfPTm29HBUwfm374YV\/8P2+4Tv3q16dovfnHDfui9n3eXWw5vwlXS9MpE8MyNlHP30GivW0rMXpu8cTaYPvXWi3iTV2Bt9vwvL0bJ2+8f8jk3FsOTU1dPrze8c6y7KZ2IJsjZ47KtEpGz\/8ihHgelUzNXLkauXvF1nuzFR1YFNv7l0tsX59LNPf5DnXLMF99NO94Za8dcLSBx\/d3g5KrD9+xof4uUmL02OXnmffvfn9y3reRvbazE1+0er4PmiWzeoVefa5ZrliRt80i0XvJ3a0m0SpqOXQunqNl\/4rXBbUREnW3etvnNFiemZiXuz12LZuxdJ44dbG8gok5fe0tkxeFtIKKNlZVNe4uniVaJyNbc3NKyjdKORJMtkmpoQMoVWYv+Zm7T5jvyxlinEnPM4TR6tcSUXgmHV8n57BtHht0SKe\/sBrof\/rufhWhgoGlmamnTsXtktGXu8uTSusM3Eji2b1s6FU2Qs1\/ezpecXl+H+mMqvU5ElF6aPH0lEt+0eweOHHux7FYuH6IdJ11bTWTI0dambBo1te\/b1+kxetPdXNKpxRSRu1uJTXJ3D3a3b5OI0ologty+R59Myas\/Gx8fH\/\/Jzy4vST7\/iA8xVyK9vpQicu5uVsfc274Nn0eFpBMr62T3+pSNosfvbMkmESWj0nMnj3TZ1xeuXk09+0ZgwLke\/U14JU1r8ZVNR8t3BSBdYNHJmH3o1KnR3RSfuhxJ1Oafk0e0bVK1jfnTx8\/Lh0Z8R88d68AMrUya6NFhpql1IiLn0FvvdMZXM47d7kcT0zlw9FBvk5ReW4mELl8NXnFjx7QiNiKijNFrYRISEaULFUIiImdnb7vbsemmuURbT7vbIzXbpqKpDUqvRVPyJ3+CiDbnzhyfe\/QbNt\/RXx1rJyKi5sHB9m1N6f62KwsL0cTGfrcRWwSiVdKmZo+d5qLRFHncDW2HTp1aT1x\/\/+Jc+V+E70jOdictrMzGNro7Gtz73zjVu7Z06Uxok2gjEU3Z3O1OiVJERDa3u8WzjcjjcSSvz4Si84l0O45HayU52900tTQfXxvc1kRElF4JX7lt6z+wrwUb93kkp9dJ8fh3U2xt\/qPJRPtzg14iImqwEZFNIpIkeUdeIiJKJ5YSGWfX49Joaxs5NvRogkqOvJmaITKuoom2dy+17O93UXLy3X+YuD0fW00szUSWNslux9u7Etu6hnfbMwvn371yc35xJbEyG7m5SmS3Z1PRBDl9yu5oJpGIraysxBZv37iZJHK3OJBzBRp8Q\/0OWrr47qXw7Pz8zY\/efzc0M7O0YfRqiUnyDD7bZlufevf9j27Oz8+GP3z3\/NTMbCpTcsalE0spm7vtu0PPmfRjG6nE\/UdbuKvhmdj9xGwkliGXz5ANUhJvm5Qkz\/DJE01XQtdvXF64QWR3en1DR4cGsdNZkabOV08eCl2ZvH31fITI5nC194+ODfU2zP9y3eb2KgehU1Nng1NERGR3tg0cOdSNM\/cVkVoOnjpqvxyKhC7OENmcbf2HRv0d2CAtbNu+YyfpypXfRK6enyKyu3b7A2ODblpbLPobmWQ0Qc4DbunRoarMUujsGeX\/OgdOnepNp4lcLYkrb\/9iddPe9uxYf+krAfh5At+ZDwDASLS9ewAA80ElBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAFSopAAArVFIAAFaopAAArFBJAQBYCfed+bJ4fCUyffP2Z9N3l5d3trb2Pt3X37fP6\/UYvV7mhlTBLEw3V0X8zvx4fOXSpV9P35pWP9n3TN\/Y2CsiRyk4pApmYca5WnibdHx8XH5w4cKFGq7MI5Hpm3KIU+Gw\/MzA4OD0remWJ1u93r+q\/froBanyYGyq4rh3714oNPHgwbeNjVv9\/uHt27ezLE2cuTowOCg\/KfhcLVBJx8fHlfjUj2vm9mePPovkEJV3\/u3Ppkf\/m6A5loVUeTA8VXGEQhNP9fTs3bPrzhdfhkITr78+XvWiDE9VmasypZ6KPFfLbJMWFAwG+azMI3eXl5XHyhtefp7rXx0IBPgtnIRJVR0pIVVzyk\/1wYNv9+7ZRUR79+z69JOPGZcvyFzNf17YuVq4kmr5FDpx\/LiWv2Dp7ldtO5+saGQsFlOiHBgcVN75O1tb5b+0imWWdea997QMYyFUqsoApKrgkUDNUm1s3Hrniy\/lbdLGxq1allOCOHNV\/cEv8lwtUEkvXLig\/kSq\/bZ979N9co45W0+9T\/fVeE10JE6q9DhYuZ4iVWvw+4dDoYlPP\/lYPk7KsijDU1Xmas6RKJHnavXbpPz09+2LfbU8fWtavenU90xff98+A9eKHVLloZ6rp9r27dtZjo3mEGSuyn+UZ6zgc1XE60mbd7jGxl5pebI152qy5h0uo1fNxJAqmIUZ56qIlZSImne4Rl7yj7zkN3pFLAWpglmYbq6iWxQAgJWg26QAPKx+k8zvQRR5n9EUkCoVuzJfeVz2wHM6k5FsthI\/TTGyiuAqhVR5qCjV1W+S6h7Eu8vLd5eXY18tj4290rzDhVQVSLUKZc7dl+1wkFei9E\/xRxajbwceUpUZlWpOv6x8RljuQRx5yW\/2VPVVdaqk6kKuq1RxnLQUuQMvEAg81dMTCk0YvToWYVSqOf2y+c9DFdSpqoOtt1Sr\/waTGrSvGI5TBx5SNSTVEj2I9RB7RapINb8Lua5SrX7vHh14lUKqMqNSVfcgqnHtQRSwr1GL6lKtWReygKnqsHcvH9bN\/2nekQq\/f\/jzmZlgMPj5zAxjB16lzJKViVJVeg3lrafSPYimS9Uo6vSmwuG6TZX1+0mVM1\/5P3NOipllpJq+HXhIVWZUqiX6ZYXKqrpU9YVUK1Xg9ytquVWvSulrCwwfWWxYbaYmUuWholSL9SC6XE6kqoZUq1D9InI2jEtsOYszssQwQYiTlfaRJkrV5XK+8PzwC8\/nHlJAqlVAqmqsx0mLbTDnl3mzjBSBWbJCqkjVLFnxTpW1khar8fmV3iwjRWCWrJAqUjVLVrxTLVxJxx8r+\/tm+ZwR4XMeqfKAVHlAqpUqsJSK7odlls+Z6j6RdOxrRKoKpIpURR6ppj3VMnv3df6F5Jz6GpEqUtUdUuVBe6pFt2zLXlB29uxZllU0BX37GgmpEhFS5QOp8qA91aKVVI6vxIGSo0ePalmVePx3Xu8PxR9ZcFro29dISJWIkCrzSKQqYKq1O4ZtRjrerxEUSJUHpMqD9lQLVFL1PVrr\/CiJjn2NSFWBVHlAqjxoT7XwNqn2+KRyX1Ut7EjlSY3\/UnZIlQekygNSrRSvK\/Mp70It0UZSuQsgDCRaVkgVqRYjWlZGpcrlO\/Plf4OWtTRqpJJyLa92ZoRUeUCqPNRhqlxeG+2V3qiR4n\/U50OqPBRb1WQylX+\/TJfLybLM6kaKn6o4WWkfqXuqhXuclMd1frxZR0iVB06pJpOpYvfL1FggTK2iVOs8K0WZM05le8Wszai7YFqb+KmWuF9m\/nfHWZIud2ytk6xkuLdoKbi3KA\/ip4r7ZWqHO7bKqj9Oil4xHpAqD5WmWuJ+mfXwAmkkR1Hijq11lVX1lRS9YjwgVR4qTbXE\/TLlRQmYau3JUZS4Y6sIWdUsVezdl2LgvUUtTPxUK7pfZp2r6I6tFlZ4mxS9YjKj7oJpbeKnWuJ+mXr9FYLT5d6iHNdPPIX77mu\/HpaHVHnglKrL5Sx2v0wef51oKkq1zrNSmKZrAqCWit0vE\/IhK8JxUgAAdjXdJmXpKoNikKqxkD9QLc84oasMqfJg7Hk8q+aPs6OVKrB3L\/eHybTcppWIHj58mPMgn7qrTLlUYvrWdGT6ZtXL5DdSd0iVB06pah+Zk7\/8EtQ4f90ZnqoII2XBYLDg43z6HCfdsmVLzoN8lXaVaVkmv5EiQKo86PvvqrSvFKmaZaQsEAjIBTQYDAYCgRIjy+zdl1B1B17+84L0bPCGVHngkap26vzVraVmz9\/YVIUiF9PSZZS0fBdUsd9k6cBTE6SrrAbTAqnywCNV7SNz+kqVYso1f8unasjIYqnKZbRsMa3dVVDoKuMBqRpLnb96mxT5W4NSQJXd\/GJqdxUUusp4QKrGQv7Wpt4OrXibVH3dg5ZrIPJPhxU8LyZ3lY2OvryztZWIdra2jo6+XOxiEY3L5DSSB6TKA6dUtY80PH8eDE\/V8JFV0GGbNP90WLHzYtq7yrQvk8dIESBVHnj8u4zNXwTGzitBUuV4nFR7vTd2pLkgVR6QKg91lSprJS2xwZxT7ysamUym\/vGfJo4fP\/7cgQPHjx\/\/x3+aSCZTLMssO14onFLVfWTZ8UJBqjwgVVmZe4tSuQMlPDatHzz4g8YOvEo31w3caTI8VX47QUhV35HVjdcRUq1CmetJDcHjzo4PHz40\/EiT9VqYq0tVfWmelmueS0OqMqRaGu9URfxWPR53djS8jFpSdalq78CrT0iVB96pVv9dUDXowLPYnR2NTVUoGjvwtECqCqTKg9ZUs3kOHz6c\/2QtHT58+EcDAz8aGMhms\/ID+T\/DV4yFqVded6dPn1Z+skCqakiVB42pirh3jzs7Wpv2DjzQDqnyoD3VJ7LZbM5TFZ254yEeX1Gfu5f1PdM3NvaK1+up8croxfBULQmp8oBUq1CgkoogHl\/Jv6ODecsoAFiboJUUAMBERDxOCgBgLqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAFSopAAArVFIAAFaopAAArFBJAQBYoZICALCyGb0CROnEbOjK5OxSapPI5vB2P3fQv8\/TQEQbs6ePX3Ic+eumK6cj6\/JYm6PZNzh0cH9Hk6GrLLz74b\/7WSip\/NHu8u0fO7S\/pYEovfgPPz2zPvY\/TnY3EW3Mv\/8356MZe9eJ\/\/lqu2Tg+prLxuzp4xfj6mdsu0+80x3624ur3z3lfPatd4bdtV41E0vHLv1NcGaT2g6990Z3g9FrUzHDK+na7PlfXoySt98\/5HNuLIYnp66eXm9451h3UzoRTZCzx2VbJSJHz6HRLml9dfb65OTZeOrEOwfxzi\/H3jV6ZNAtpdfnJz+8MXkp0vXf92+jVHw149jtbiAi2lgKL2bITpvRSCzdjkArYvMOvfpcs\/z+kaRtnib72NGja0REmWTkUijqbnEYun5mk46F5zfJbqOlSHStu9t0m0pGV9L7c9eiGXvXiWMH2xuIqNPX3hJZcXgbiGhjZWXT3uJpolUisnu9HR3bqKPT51x\/82Jkcv65k+YLu8Ykh6elxSPR2oZTuhFvcEhEtJGIpmwen1Mioo1oeCnjHDjYNnd5NryYbu9AKa2A5PT6Or736dPQ0eQhovs3fxPdbB4e6TTfdpWB0rFIdNPeNdofv3wjvLTW3d1E6cUPf3om2j7gux+ZS1LzwKv7NycuzSTJ1X\/spFwuRGLwcdJ0ajFF5O72PM5FcncPdrdvk4jSiWiC3PJ7XqWprctNlFpKpWu+smazPvX2T8bHx8f\/9uxc2jt0sLuJKJ2KJsjZ5m4gorVoZIkcu3s7+9vsmWhkccPo9TWdQlNwYz4Uijf0H+zfVvPVMTO5kPr6O3u7nBQPR9eIiCSSaDOW6nztpL85szr1Ydjz6s9HfZSMTEbFm6xGb5PK0kRE6ZWP3nx7ap2IyDn01judqt3Q749NExFh+6kse9ehY\/vdUnojMRu6NHn6Q\/c7r3lXYut2j7eJiNaWwktk7+l2N7h72mxz0anYRkeHaB\/0AtucO3N87tFjm+\/or451SESUiEwsZJpHnm3B\/KxEejE8v2lr7\/Y0OBt8jhuRSHStt1eejJ5+n9udanPQqqO\/y+NO+5wU3VjfIBJsrhpcSSVnu5MWVmZjG90dDe79b5zqXVu6dCa0+Wg31N3ulCj1vd9YW5pJErXlbatCHsnh9ng8EpHHfSA8dX5pNrFhj6bI2e+WiNai4TgRzQR\/MiOPXpxa2ujAHqlmtraRY0MeeRZKDvlBeuVmOEne0U5skFZkYzESzVAmev74uPzEeji61ttNRGSTSCKSpMc\/jK5YRRm9Xtu6hndfO79w\/t0r\/gO7nbS+NHtzlajZnk1FE+QcbG4guZJmVuOLi4n11blroTg5B4Z8eMuXlV5PrKyQlN5IzF5fIXK2ODdWVzYd7Z4GorVoJE4238ix5zwSUToeej+0GFna6EQp1S6TTj\/av0+nEvebPNuktaXoOjkHW3AEvyIbsalohrxDR\/1tDUTpxPXzlxfC82vdHqNXrBJGV1Jq6nz15KHQlcnbV89HiGwOV3v\/6NhQb8P8L9dtbq8yJVORi2ciRGR3+Z49OjqMnScNNucuBuX9T5vD2zM61t+QOJ8ip88tyVuk9p7netvlJN3P+SbORsMopdpllkJnzyh\/cg6ceutF93psncjtxkn7imwsTS1mbL79\/R0tDUREngPdkwuRyPz9g0avWSWeyGazRq8DAIC5occJAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwMrw78wvLB5fiUzfvP3Z9N3l5Z2trb1P9\/X37fN6TXU7glpBVgCGE\/E78+PxlUuXfj19a1r9ZN8zfWNjr6BA5EBWACIovE06Pv7oHn8XLlyo4co8Epm+KZeGqXCYiAYGB4lo+tZ0y5OtXu9f1X599MIj1ZysiGhgcNACWWln7FwVx71790KhiQcPvm1s3Or3D2\/fvp1laUhVVkGq2TyHDx8u+LhmDh8+\/KOBAfm\/bDarPDZkZfTCKVV1Vuq4TJ2VdobPVXGcO\/fB3J2FbDY7d2fh3LkPWBaFVBXaUy2zTVpQMBhkrPSl3V1eLvY81786EAjwWzjxSVWdlbJZSvyz0s6MqYovP9UHD77du2cXEe3ds+vTTz5mXD5SlWlPtXAl1bJJf+L4cS0rt3T3q7adT1Y0MhaLFSymO1tb5b+0imWWdea997QMY8EjVXVWA4ODSjHlmpXlUxV8ZMFUGxu33vniy717dt354svGxq1allMCUpVpT7VAJb1w4YL6E6n2B0p6n+6Tq4NcF6bCYflQae\/TfTVeEx1xSjUnK\/XzuixfcIbPVXH4\/cOh0MSnn3wsH9FjWRRSVWhPtfptUn76+\/bFvlqevjUtF1BZ3zN9\/X37DFwrdjxStWpW2tXz+1xt+\/btr79eape8IkhVpj1VEa8nbd7hGht7peXJ1pxrJJt3uIxeNeEgKwARiFhJiah5h2vkJf\/IS36jV8QEkBWA4dAtCgDAStBtUgAwi9Vvkvn9yvV2fKlAJa3otF06k5FsthI\/TTGyiuAqhVR5QKo8VJTq6jdJdb\/y3eXlu8vLsa+Wx8Zead7hqp9Uy5y7Hx8fLx2lvBKlf4o\/shh9O\/CQqgypWinVEr3dIy\/5zZ6qdjhOWkooNPFUT08gEHiqpycUmjB6dSwCqfJgVKq3P3u0Naq+Dk\/9fJ2o\/htMatC+YjhOHXhIFanqzqhUS\/R210Psiur37tErVimkKkOqjCOFStWQ3m4BO5t12LuXD+vm\/zTvSIXfP\/z5zEwwGPx8ZoaxA69SZskKqdZ5qkpfstLbnfO8mulS1Y71+0mVM1\/5P3NOipllpJq+HXhIVYZU9RqpZlSqJfqVhcqqulS1K\/D7FbXcqlel9LUFho8sNkyvk3elIVUekCoPFaVarF\/Z5XLWVarVLyJnw7jElrM4I0sME4Q4WWkfiVR5jDRRqi6X84Xnh194PveQQl2lynqctNgGc36ZN8tIEZglK6SKVM2SFe9UWZdSrMbnV3qzjBRBRf+uZDKV36vncjlZlqn7SBGYJSvTpVriZ85IU+RfncKVVPvx5hJHH3JW0Swj+eGRajKZKtarlzNBkapZsjJXqpirsgKVVH0FWdleMbN8elf3iaRjBx6nVEvcWzTnuJU4qeqoolRL9DUKlZW5UsVclZU5TlrnX53NqQNPx1TVvXrqy1DqrVePNKSKvsYqYK5qVPQ4adnN+7Nnz3JZI5Ho24FHHFJV2kty7uN0d3m5Hl4gmcZUS\/Q11k9W2vGbq5R3H1wL5F+0ksrxlbhZ69GjR7X8BfH477zeH4o\/suBrqW8HHnFINefeosqAna2t8qIETFV3GlMt0dcoQlYmTbWsYnNVfR9cEfJnTBXfBVWKgX2NGql78qbC4dK9enWuor5G0J06f\/U2qTXyL7BNqr5Ha50fJ9WxA49TqnV+b9GKUq3zrLTDXK1C4b177fFpv6pAtJHKkxr\/pex4pFqiV6\/qZVo1VdGyskaqmKsyXlfmU95aijaS9LsAQncV\/buK9eoh1RyiZWWZVAv+pDqbq1yOk0qPv1ZA2JHS4xYxScgOvIKQKg9IlYc6TJXLa6O90hs1UvyPeo19dQUhVe0wV3kotqrizGrdUy3c46Q8rvMzTjqqKFXtfXV1DnOVB06pWntWlznjVLZXzNoEvF9j\/jGmOoe5KjNqrmqnvVvUjHA9aSm4XyOYhfh3bK3TbtGyLNDgVZbu3aJloa+Rh3oIzai5qp21O5urr6ToFuUBfY08YK7ygM5mNezdl2KK+zUCEDqbjcb6Tc\/WJuD9GvVaGcvAXJUZNVe1s\/asLtx3X\/v1sLyKUnW5nBr76uoc5ioPnFK19qw2TddEvSnWVwdgXhae1ThOCgDACtukAAWw9DVCHcIZp9pBqjzwSNXafY1aYK5WivXeorKHDx9u2bJF\/cDsI2XBYDAQCOQ\/rgJSVRh1x1bta1tpX6MIqeoIc1Whfa7qc5xUWaeyK2eWkbJAIBAMBom5jFbHLFlVmqqxfY1a1rbSvkYRUjWWWWYgv7laZu++BEHaV3iTi6kuZRSpyvTta+SRqrX7GrXAXJVpn6vlvwuq2G\/WQwcePd4a1aWYIlWZvn2NPFK1dl+jFpirMu1zFVdBlaIUUGU3H9ihrxHMQvtcxVVQpai3Q2t\/nNSq9O1r5MHafY2gnfa5WmCbVH2qTuNpu2IPTDqSB6TKA6dU5b7G0dGXd7a2EtHO1tbR0ZeLXQKFVM0yA7mmqsM2af7psGLnxcwyUgRmycqqqWrva0SqZpmBXFPleJxUe703dqS5IFUekCoPdZUqayUtscGcU++NHVl2vFCQKg9IlQekKitzb1Eqd6DEXJvrBu40IVUekCoPSLUKZa4ntYyHVbXc6dgtSkj1MaRaWnWpcrq3qGXwnqv1cj1pdZ88xnaLig+p8lBdquLfW9RYvOdq9d8FVQ+9YsSnWxSpIlXdcerBrfNUSftczeY5fPhw\/pN16\/Tp08pPFkhVDanq7ty5D+buLGSz2bk7C+fOfcCyKKSqpnGu1svefXXQLcoDUuVB\/B5cM9I+V5\/IZrM5T1V05g40Qqo8IFUekGoVClRSAACoCPbuAQBYoZICALBCJQUAYIVKCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAlc2ov3jt5i\/\/9uoqOQZOvfOiRyo\/Pr3y0ZtvRwf\/\/pj0\/s9CyUdP2l2+nuGR4c5tGhZQb+6H\/+67oIiIyNF\/6p2DWrIGbdKL\/\/DTMwsZ+Q82p7dz0H9wsKXB2JUyq++FSWRzeLv9hw52N61ceTMYWf9uXNvRc290CDiJjaqk9+cjq2Sz0\/rc7cSwllK6sRJft3u8TbRKRPbdI4f6nZvJuesTU+ffTh1567XOJv7rbEJyUPJjqcHtFnAGmp7r2SP+NlpfikzeCAVX1k79fBifVlVzDZ0Ya5NoYyV8+erMpatdncfaBw8d3Z0mItqMhi5OZbzbjF7HwgyqpPcXIqvUPDTUdO3qXCQxfNAjydtQNDDQNDO1tOnYPTLaMnd5cmnd4RsJHNu3LZ2KJsjZ75ZolYgkZ0t7h0fq6PC50m+enZuYud+5X9CAjfUoKOXP6ZWP3nx7yjnQTzOReNrVMzZsD1+aim86u44EXsWnUVUaWto6Ohqoo7Ot6Zc\/C4WvLR54TcRtJnNocHtaWhoo3RB30Fza0UAkuds73ESUjl25nLT3nNgv6OaAMcdJ7y\/cXCVXb1dnf7ttfT6SSBORZJOIklHpuZNHuuzrC1evpp59IzDgXI\/+JrySprX4yqajxZO759TQ0u0hSi0l0kb8M0xIkiSilbj74BujPkrOXJyQ\/CePdtlTcxNz941eN7Nr8vmclFldTRm9IiYWP398fHx8\/Ce\/CK06u8aGWh5XzXTi2qXIept\/qF3UgyeGbJPeX7iZJOeQr6nJ3uOh6Hx4xT\/WQkREzs7edrdj001zibaedrdHarZNRVMblF6Lpsjtc0q0UWiBhh3tFd361Ns\/mXr02NF\/6h2\/RETk7unyeNK7nRTNdPa2uB3rbppL3d9ME4nKx4xGAAAVOUlEQVT5cW8W6TQR2TAbGTT7T4y1NaTTa\/Hrl0Pn33U+OlaysTgRTtp7DnaLu9tkxKt+f+5mkogmf\/GTSfmJ+chKukU+mtdgIyKb9GjjiUj+mU4sJTLOLncD5VTSjdjtFSK3T9AtfsPZu0aPDD4KR3K6JZK3lxrkbImoSZkAmQK\/DpVIRaMpsnd5xX23i09yejyeBiKP50DX9YWp+djasGcbrUVvLGTsPf0tAr\/NDaikiblIkhw9h470O4loY\/7S2RvzkZX0UIlfSSeWUjZ3m1P5cyq2uLiWSSxcDy1s2ruGunGQtIh0Op1+dOQjnUqsSQJPRbPaiMUW7ZSKR34zmaTmoQPtyLh66dTKykoDpddi4bl1sre5m4gonZiNka29S+gzebWvpInbkRQ5n93f3eImIiLnfu+Ny\/Ph2IG2or+SSUYT5Dzw3Ybn5sLVswtEZHO2DRwZHe4Q9diJ4R4HJXON\/P1rBq6MRSVvnD1zg4jszV3+wMFBt9HrY2qroTNvy4\/sLt\/QoYPtEhFtJFIZanCJva3\/RDabNXodAADMDT1OAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWKGSAgCwQiUFAGCFSgoAwAqVFACAFSopAAArVFIAAFa4exdAAfH4SmT65u3Ppu8uL+9sbe19uq+\/b5\/X6zF6vczNwqniO\/MBcsXjK5cu\/Xr61rT6yb5n+sbGXrHG294Q1k618Dbp+Pi4\/ODChQs1XBnh3Lt3LxSaePDg28bGrX7\/8Pbt21mWhlRl4qcamb4pv+GnwmH5mYHBwelb0y1Ptnq9f6XX3yIy3qkODA7KT1on1Wyew4cPF3xch86d+2DuzkI2m527s3Du3Acsi0KqCvFTPXz48I8GBpT\/stms\/KBOXjjeqWazWeulWmabtKBgMMinqhssEAjkPPPgwbd79+wior17dn36yceMy0eqMvFTvbu8LD9QtkmV5wV5mfJT1RfXVPOft0CqhSuplk36E8ePa\/kLlu5+1bbzSfFHnnnvvfwnGxu33vniy717dt354svGxq1allMCUpWJn2osFlPe9sp+KBHtbG2VFyVgqvrinar6I8oaqRaopBcuXFB\/ItXzQT2\/fzgUmvj0k4\/lI3osi0KqCvFT7X26L+c9L9fT3qf7dFm+4HinKoepFFNrpFr9Nmk92L59++uvl9rNqQhSlYmfan\/fvthXy9O3ptUbpH3P9PX37dP97xIT11TlP8rZWiZVXE8KkKt5h2ts7JWWJ1tzrnxs3uEyetVMzNqpopICFNC8wzXykn\/kJb\/RK2IpFk4V3aIAAKx02CZd\/SaZ3wFmjS12gLIw\/4EKVtKKTtutfpNUd4DdXV6+u7wc+2p5bOyV5h2udCajjExnMpLNVuKngSMrDK0aFaUqclZIVT2y9vOfMTEtDE\/VjHO1zLn78fHx0lHm9NXJ5+PkDrCRl\/zqVZQfl\/5p1Mja0J6qyFmxp6pvt6ixqZboK+U0\/2sDc1Wmfa6yHie9\/dn3rmnIfx4gRyg08VRPTyAQeKqnJxSaMHp1mKjnv\/otgPlvDdrnavXfYCK3BJToAKtBJ4a5aE\/V2jh1ixqSqnr+q\/t2zD7\/MVdl2udq9Xv3coOXugNMTZAOMDE78MqmWpapU+XULWpIqjl9pUox5Tr\/MVcFnKuse\/dKp5c8h0p3gMkHgPN\/ijZSBGbJqopU\/f7hz2dmgsHg5zMzjN2ildL936We\/+pt0prNfxGYZQZynaus309aoq8u56SY8sf8n0KN5Ed7qmbJqrpU9e0WNTZVY+c\/P5irMu1ztcDvV9RyW6wDzOVy5qyceqVLX4XAaWSxYbWZmhWlanhW2kfWeaqc5n+dp2rGuVr9IpQNY5fL+cLzwy88n7vpqwzI2YQusY3Ne2SJYYIQJyvtI+s8VU7zv85T5TSSX6qsx0mLbTDnl3mzjBSBWbJCqkjVLFnxTpV1KcVqfH6lT2cyyWQqv6\/O5XKyLFP3kSIwNgGkirmqHVKVFa6k2o83lzj6kLOKyWSqWF9dTpTal8ljJD88UjXLSH4wV3lAqpUqUEnVV5CV7RXTXulL9NXlHGMy4ydSWZxSNctINR27RWszV9U90JirOVABZGWOk+r41dnoq1PU+Zfnc+oW5TRXCz5fP1ABNCp6nLTs5v3Zs2cr+ptK3K+x0kWZl+6pmpG+3aLEc67mP18PL5CMa6o5nbUWSLVoJZXjK3Gz1qNHj2r5C+Lx33m9P6SS92uUF6WM1L5MHUfW5rXUPVXBRxZMVd9uUeI8V9UwV9UYK4C6s9YCqdbuO\/PV\/XPq1jpr3FkQtDOwW1SjinqgQaOKOmtNp8A2qfoerToeJanz+zVyStWMdOwWxVzlAalWofDevfb4tF9VUKKvruplsoxUntT4L2XHI1XRRpIlUsVcRaqV4nVlPuWtZTqTKdZXlz9S+zKrHkn6XQChu9okgFQxV9khVRmX46TS468VEHak9LhFTBKyA68gpMoDUuWhDlPl8tpor\/RGjRT\/oz5fsVXV2IFX0TKrG2mlVMUZKX6q4sxA7SN1T7Vwj5PyuM7PjeiIU6raO\/AsCXOVh4pSrfMZqChzxqlsr5i1GXUXTO20d+BZFeaqzKi5qr2z1tpqdz2pGYl\/F0xrd+CBdkbNVXTWyqo\/TmqBBq+ydO9rLAs9uDzUQxRGzVV01sqqr6Toa+QBPbg8YK7yIKcqeGetBbtFzchEfY2EHtz6ZtRcRWetjPWbnq3NqLtgamftDjwtMFdlRs1VzEBZ4b772q+H5XFK1eVyauzAsyTMVR4qSrXOZ6DCNF0TUEyxDjyA2sAMJBwnBQBgh21S02Pp1QOoJQvPVZxxqh0eqaJXD3OVB8zVShXYu5f7w2Ql7j2g9vDhw5wHZh8pCwaDBR9XgVOq6l495QKU6VvTkembVS+T30jdYa4qTDdX5elqmbmqz3HSLVu25Dww+0hZIBCQJ2UwGAwEAlp+RUda1rbSXj0RUjWWWWagteeqls5mEVLVrszefQmCtK\/wJk9QXaYmj1TRq4e5qjDLXLVkZ3P574Iq9pv10IFHjz\/hdZmgPFIVvFevBu8QzFWFieaq9TqbcRVUKcqkVHadRINePZCZaK6SFTubcRVUKerP9tofe9ICvXogw1w1VoFtUvV1D1qugcg\/HVbsvJhZRvLAKVW5V2909OWdra1EtLO1dXT05WKXlSBVs8xAS6Zq7bmqwzZp\/umwYufFzDJSBNrXVnuvHlI1ywy0aqoWnqscj5Nqr\/fGjjQXpMoDUuWhrlJlraQlNphz6r2xI8uOFwpS5QGp8oBUZWXuLUrlDpSYayfIwJ0mpMoDUuUBqVahzPWklvHw4cMqIlNfmsd+mR5SlXG6C6ZlYK7ywDvVermetLpPHmM78MRXXari37HVWJirPPBOtfrvgrJAg5cWPDrw6jxVfe+CiVQVmKs8aE01m+fw4cP5T9at06dPKz9ZIFXFuXMfzN1ZyGazc3cWzp37gGVRSFUNc5UHjanWy959dcTvwDMj8e\/YakaYqzxoT\/WJbDab81RFZ+5AI6TKA1LlAalWoUAlBQCAimDvHgCAFSopAAArVFIAAFaopAAArFBJAQBYoZICALBCJQUAYIVKCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKxQSQEAWNmMXoE8G7Onj1+Mq5+x7T7xq9faJaNWyLTSKzevXLkxG09liOxOX8\/I6IsdTUavFIAliVdJiYjI5h169blmeeUkaZsHZbRia7ffP311SWobGBlqb1iPhiemzr4rnfr5MLIE0J+glVRyen0d2AxlcH\/u+lLG3nPs2IvtEhF1+tq8Mwm3I7344U\/PRNsHfPcjc0lqHnh1\/+bEpZkkufqPnTzY3mD0WgOYlLjHSdNGr4CppVOLKSJ3p7IF2uDpHexuaZIkkmgzlup87aS\/ObM69WHY8+rPR32UjExGNwxdYwAzE3SbdHPuzPG5R49tvqO\/OtaB7VMdefp9bneqzUGrjv4ujzvtc1J0Y32DCBulAFURtJLa2kaODT3anpIcOLRXMcnZ7qaFxGxso7OjgYhoY3EiNO8ZPOAkIptEEpEkPf4h6iwAMA1h9+4z6cc2Uon72NWv1LauA7vtm3Pn379yc3Z+9uaVd9+\/MbMQQ44AXAi6NZJZCp09o\/zJOXDqrRexYVqZps5XTx0KXZ6cvXoxQmR3+Z49OjrcIsXCRq8YgAU9kc1mjV4HAABzE3bvHgDANFBJAQBYoZICALBCJQUAYIVKCgDACpUUAIAVKikAACtUUgAAVqikAACsUEkBAFihkgIAsEIlBQBghUoKAMAKlRQAgBUqKQAAK1RSAABWqKQAAKwEvfsIyAYHB+UH4TDuGgIgrsKVdHx8XH5w4cKFGq6McO7duxcKTTx48G1j41a\/f3j79u0sS0OqAFZVoJKOj48rb3X14zoUCk081dOzd8+uO198GQpNvP76eNWLQqoAFlZmm7SgYDDIZ2UMFggEcp558ODbvXt2EdHePbs+\/eRjxuWzpGrezPNTBbCewpVUyxbTiePHtfwFS3e\/atv5pPgjz7z3Xv6TjY1b73zxpbxN2ti4VctySqgi1d\/+9rcFnzd1qgDWU6CSXrhwQb31VM\/7oX7\/cCg08eknH8vHSVkWhVQBLKz6bdJ6sH37dpZjozmQKoBV4XpSAABWqKQAAKxQSQEAWKHHyfRWv0lGpm\/e\/mz67vLyztbW3qf7+vv2Ne9wGb1eAHWk8JX5yuOyJ0nSmYxks5X4aYqRVQRXqepSzX8m5\/nVb5KXLv16+ta0\/Me7y8t3l5djXy2Pjb3SvMNl+VQBBFHm3H3Zbhz5DVP6p\/gji9G3W7S6VIutvywyfVMuo1OPG\/MHBgenb023PNk68pJfzFQBrAfHSUuRu0UDgcBTPT2h0ITRq1PA7c8ebY0ODA4OPP66E\/XzAFAD1X+DST20r3DqFq0i1WLP311elh9Mff\/Lou4uL9fDCwQgiOr37tEtWqkqUi3bLRqLxZRiqt4m3dnaKv+KgKkCWI8Oe\/fyKYj8n+YdqfD7hz+fmQkGg5\/PzDB2i1Yqf90Krm3v033K46lwWNkyVT+fswTDUwWwHtbvJ1XO0ub\/zDmBa5aRavp2i1aRqlrBte3v2xf7ann61rR6g7Tvmb7+vn3CpgpgPYW\/wUT776vfNqWvgzF8ZLFhtXnDV5eq8kyxf1fzDtfY2CstT7bmXE\/qcjnrIVUAQVQ\/3XN24krs5YkzUvxd0RLrU+zf5XI5X3h++IXncw8+IFWAmmE9Tqr9ikKzjBRB6Ss3i41EqgBGYZ3x6UwmmUzldyu6XM78kSV+ijNSBPnrpksCBr5SANbGesYpmUwV61bMeYuWOKaW88YzdiQ\/2lPNPyjJ\/u8y9pUCsLYClbSie7fldCvKZ5DlbsWcI3d1vk1aUao8tknVr5Ryor9mrxSAtZU5Tlp260ndrVjwechnyJfn57wiypWneKUA2BU9Tlp2V\/Ts2bOk6lbMcXd5WR4AahpT1f68dnilAPgpWknlt3qJGwsfPXqUvt+tqLaztVUeEI\/\/zuv9oZZVMXZkbaqJxlQVSrdozvNVJKB+pdRN+lxfKdRoqBOsV0EpXYnym7N0tyIYSHlFcr41Cq8UALvCPU7azzKX6FbUcS0toKJUeVBeKfmP8uuFVwpAF2W+C6qsEt2KOSO1XytTm5HKkxr\/pey0p1rsKqgSI8smUJtXqsSqAliYDlfmF+tWzHlHlbhWxpCRJPDFOsWugiK2BGrwSpHAqQLww+U786W8L+AQbaSyuWeivkakCiAsLjO+2PtNY7diRcusbqQZN6DYE+CdvxlTBdBF4R4n5bGO50a0dytaEqdUtavz\/AG4KnPGqWxfo3Yl7oKZf+TOknikqh3yB+CndvcWxV0wjYX8Afip\/jhppe0r6lYodY8NuhXVatMtivwB9FV9Jc3pXyymYLfiwOCg8mYWpK9UkGpSm27RmuUvSKoAvNXuapXep\/vkd3LOndnRrVgbyB+AH9ZvetYOfaWCdIvWbf4A\/LDeW1Q7l8upsVvRkgypnmp1nj8AVzXtRSnWrQi1gfwBOKndVVAAAFZV021Slm5FAABh1e6ME7oVjT3jBAD8sN5bVPbw4cMtW7aoH+SrtFtRyzL5jdRddalqXLixWRmYKoAg9DlOqrx\/SryRKu1W1LJMfiNFoH0ljc3KXKkC8FBm776EqrtFc64Mr59uRZZU6yQiAJMq\/11QxX6TsVtUGVA\/3aJVpKpjt6ghI\/EBAHWidldBqbsSp8Jh3IUUACyjdldBoVsRAKyqwDap+rRyRaeY8x+oyd2Ko6Mv72xtJaKdra2joy8XuwRK4zI5jeSh6lRLPJPzvCFZGZsqgCB02CbNP3Vb7Byu9m5F7cvkMVIE+etWywSsmioAJxz37rVfXWjsSJFdu3Yt5xmkCiAg1jNOJXbuct5yxo4sO14oSBXAXMrcW5TKHdQz166lgbuiSBXAwspcT2oZ1e2W3rt3LxSaePDg28bGrX7\/8Pbt21nWAanKgsFgIBDIfwxgXvXyrXrVbSWFQhNP9fQEAoGnenpCoQnd18rsqks1EAgEg0FCGQULqf67oOqhfeXBg2\/37tlFRHv37Pr0k48Zl4ZUFXIxRRkFy6hm775+3gCNjVvvfPHl3j277nzxZWPjVsalIVWFXEZRTMEy6mXvvjp+\/\/DnMzPBYPDzmRm\/Hzft0IdSQJXdfACzeyKbzeY8VdFZZtAIqQJYWIFKCoJ5EJv9l68f\/qc\/\/4u2JqNXBQAKqul9nKCEzNc3Jv7Z9p8b\/\/Bva86nf\/zUDpvy\/M3P6akf\/xe8VADiwnFSYWzZue\/Zwb8YHOzMzH32dUZ5+g+p3\/\/RZmt0On9g4LoBQEnY0BGFzdniIiLKPPz3LU7lMs0\/JO\/G19Yyc7M\/aP\/B\/\/lfn2\/p+NO13\/\/Z8HALyiqASFBJBZJJ\/vPNz\/\/vvzq6fux8\/Lr8wNXibfoX6ur+s6ZUcssW11P\/1fvvf0AZBRAM9u4FYnP9+eBfjvz4T+cmbiUzxQb9oAmFFEA0qKSiePDN1w+IiOhPGv\/kDw+LFlIAEBD27kXxx3\/75\/\/9\/2Ku\/\/jH5O+dfzH4+NR9Zu1fv15b++PS1ztbHv7+4cN\/\/3ptZ1sTXjQAweB6UgAAVti7BwBghUoKAMAKlRQAgNX\/B8il\/EMAMFo9AAAAAElFTkSuQmCC","base64":true}},"from_email":"salvi.pascual@gmail.com","from_name":"Salvi Pascual","to":[["apretaste@gmail.com","Apretaste"]],"subject":"pizarra","spf":{"result":"pass","detail":"sender SPF authorized"},"spam_report":{"score":1.7,"matched_rules":[{"name":"RCVD_IN_DNSWL_LOW","score":-0.7,"description":"RBL: Sender listed at http:\/\/www.dnswl.org\/, low"},{"name":null,"score":0,"description":null},{"name":"listed","score":0,"description":"in list.dnswl.org]"},{"name":"FREEMAIL_FROM","score":0,"description":"Sender email is commonly abused enduser mail provider"},{"name":"HTML_OBFUSCATE_05_10","score":0,"description":"BODY: Message is 5% to 10% HTML obfuscation"},{"name":"HTML_MESSAGE","score":0,"description":"BODY: HTML included in message"},{"name":"DKIM_VALID_AU","score":-0.1,"description":"Message has a valid DKIM or DK signature from author\'s"},{"name":"DKIM_SIGNED","score":0.1,"description":"Message has a DKIM or DK signature, not necessarily valid"},{"name":"DKIM_VALID","score":-0.1,"description":"Message has at least one valid DKIM or DK signature"},{"name":"DC_PNG_UNO_LARGO","score":0,"description":"Message contains a single large inline gif"},{"name":"RDNS_NONE","score":1.3,"description":"Delivered to internal network by a host with no rDNS"},{"name":"FREEMAIL_REPLY","score":1,"description":"From and body contain different freemails"},{"name":"DC_IMAGE_SPAM_TEXT","score":0.1,"description":"Possible Image-only spam with little text"},{"name":"DC_IMAGE_SPAM_HTML","score":0.1,"description":"Possible Image-only spam"}]},"dkim":{"signed":true,"valid":true},"email":"webhook@apretaste.net","tags":[],"sender":null,"template":null}}]';
		$mandrill_events = $_POST['mandrill_events'];
		$event = json_decode($mandrill_events)[0];

		// get values from the json
		$fromEmail = $event->msg->from_email;
		$fromName = isset($event->msg->from_name) ? $event->msg->from_name : "";
		$toEmail = $event->msg->to[0][0];
		$subject = isset($event->msg->headers->Subject) ? $event->msg->headers->Subject : "";
		$body = isset($event->msg->text) ? $event->msg->text : "";
		$filesAttached = empty($event->msg->attachments) ? array() : $event->msg->attachments;

		// save the attached files and create the response array
		$attachments = array();
		foreach ($filesAttached as $file)
		{
			$object = new stdClass();
			$object->type = $file->type;
			$object->content = $file->content; // base64 attachment string
			$object->path = "";
			$attachments[] = $object;
		}

		// save the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/mandrill.log");
		$logger->log("From:$fromEmail, To:$toEmail, Subject:$subject\n$mandrill_events\n\n");
		$logger->close();

		// execute the webbook
		$this->processEmail($fromEmail, $fromName, $toEmail, $subject, $body, $attachments, "mandrill");
	}

	/**
	 * Receives email from the MailGun webhook and send it to be parsed 
	 * 
	 * @author salvipascual
	 * @post Multiple Values
	 * */
	public function mailgunAction()
	{
		// filter email From and To 
		$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
		preg_match_all($pattern, $_POST['From'], $emailFrom);
		if(isset($_POST['To'])) preg_match_all($pattern, $_POST['To'], $toFrom);

		// get values to the variables
		$fromEmail = $emailFrom[0][0];
		$fromName = trim(explode("<", $_POST['From'])[0]);
		$toEmail = isset($toFrom[0][0]) ? trim($toFrom[0][0], " \t\n\r\0\x0B\"\',") : "";
		$subject = $_POST['subject'];
		$body = $_POST['body-plain'];
		$attachmentCount = isset($_POST['attachment-count']) ? $_POST['attachment-count'] : 0;

		// save the attached files and create the response array
		$attachments = array();
		for ($i=1; $i<=$attachmentCount; $i++)
		{
			$object = new stdClass();
			$object->name = $_FILES["attachment-$i"]["name"];
			$object->type = $_FILES["attachment-$i"]["type"];
			$object->content = base64_encode(file_get_contents($_FILES["attachment-$i"]["tmp_name"]));
			$object->path = "";
			$attachments[] = $object;
		}

		// save the webhook log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/mailgun.log");
		$logger->log("From:$fromEmail, To:$toEmail, Subject:$subject\n".print_r($_POST, true)."\n\n");
		$logger->close();

		// execute the webbook
		$this->processEmail($fromEmail, $fromName, $toEmail, $subject, $body, $attachments, "mailgun");
	}

	/**
	 * Process the requests coming by email, usually from webhooks
	 * 
	 * @author salvipascual
	 * @param String Email
	 * @param String
	 * @param String Email
	 * @param String
	 * @param String
	 * @param Array
	 * @param Enum mandrill,mailgun
	 * @param String
	 * */
	private function processEmail($fromEmail, $fromName, $toEmail, $subject, $body, $attachments, $webhook)
	{
		$connection = new Connection();
		$utils = new Utils();

		// do not continue procesing the email if the sender is not valid
		$status = $utils->deliveryStatus($fromEmail, 'in');
		if($status != 'ok') return;

		// remove double spaces and apostrophes from the subject
		// sorry apostrophes break the SQL code :-(
		$subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));

		// save the email as received
		$connection->deepQuery("INSERT INTO delivery_received(user,mailbox,subject,attachments_count,webhook) VALUES ('$fromEmail','$toEmail','$subject','".count($attachments)."','$webhook')");

		// save to the webhook last usage, to alert if the web
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP WHERE task='$webhook'");

		// if there are attachments, download them all and create the files in the temp folder 
		$wwwroot = $this->di->get('path')['root'];
		foreach ($attachments as $attach)
		{
			$mimeTypePieces = explode("/", $attach->type);
			$fileType = $mimeTypePieces[0];
			$fileNameNoExtension = $utils->generateRandomHash();

			// convert images to jpg and save it to temporal
			if($fileType == "image")
			{
				$attach->type = image_type_to_mime_type(IMAGETYPE_JPEG);
				$filePath = "$wwwroot/temp/$fileNameNoExtension.jpg";
				imagejpeg(imagecreatefromstring(base64_decode($attach->content)), $filePath);
				$utils->optimizeImage($filePath);
			}
			else // save any other file to the temporals
			{
				$extension = $mimeTypePieces[1];
				$filePath = "$wwwroot/temp/$fileNameNoExtension.$extension";
				$ifp = fopen($filePath, "wb");
				fwrite($ifp, $attach->content);
				fclose($ifp);
			}

			// grant full access to the file
			chmod($filePath, 0777);
			$attach->path = $filePath;
		}

		// update the counter of emails received from that mailbox
		$today = date("Y-m-d H:i:s");
		$connection->deepQuery("UPDATE jumper SET received_count=received_count+1, last_usage='$today' WHERE email='$toEmail'");

		// save the webhook log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/webhook.log");
		$logger->log("Webhook:$webhook, From:$fromEmail, To:$toEmail, Subject:$subject, Attachments:".count($attachments));
		$logger->close();

		// execute the query
		$this->renderResponse($fromEmail, $subject, $fromName, $body, $attachments, "email", $toEmail);
	}

	/**
	 * Respond to a request based on the parameters passed
	 * 
	 * @author salvipascual
	 * @param String, email
	 * @param String
	 * @param String, email
	 * @param String
	 * @param Array of Objects {type,content,path}
	 * @param Enum: html,json,email
	 * */
	private function renderResponse($email, $subject, $sender="", $body="", $attachments=array(), $format="html", $source = "")
	{
		// get the time when the service started executing
		$execStartTime = date("Y-m-d H:i:s");

		// remove double spaces and apostrophes from the subject
		// sorry apostrophes break the SQL code :-( 
		$subject = trim(preg_replace('/\s{2,}/', " ", preg_replace('/\'|`/', "", $subject)));

		// get the name of the service based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// check the service requested actually exists
		$utils = new Utils();
		if( ! $utils->serviceExist($serviceName)) $serviceName = "ayuda";

		// include the service code
		$wwwroot = $this->di->get('path')['root'];
		include "$wwwroot/services/$serviceName/service.php";

		// check if a subservice is been invoked
		$subServiceName = "";
		if(isset($subjectPieces[1]) && ! preg_match('/\?|\(|\)|\\\|\/|\.|\$|\^|\{|\}|\||\!/', $subjectPieces[1]))
		{
			$serviceClassMethods = get_class_methods($serviceName);
			if(preg_grep("/^_{$subjectPieces[1]}$/i", $serviceClassMethods))
			{
				$subServiceName = strtolower($subjectPieces[1]);
				unset($subjectPieces[1]);
			}
		}

		// get the service query
		$query = implode(" ", $subjectPieces);

		// create a new Request object
		$request = new Request();
		$request->email = $email;
		$request->name = $sender;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = trim($subServiceName);
		$request->query = trim($query);

		// connect to the database
		$connection = new Connection();

		// get the path to the service
		$servicePath = $utils->getPathToService($serviceName);
		
		// get details of the service
		if($this->di->get('environment') == "sandbox")
		{
			// get details of the service from the XML file
			$xml = simplexml_load_file("$servicePath/config.xml");
			$serviceCreatorEmail = trim((String)$xml->creatorEmail);
			$serviceDescription = trim((String)$xml->serviceDescription);
			$serviceCategory = trim((String)$xml->serviceCategory);
			$serviceUsageText = trim((String)$xml->serviceUsage);
			$showAds = isset($xml->showAds) && $xml->showAds==0 ? 0 : 1;
			$serviceInsertionDate = date("Y/m/d H:m:s");
		}
		else
		{
			// get details of the service from the database
			$sql = "SELECT * FROM service WHERE name = '$serviceName'";
			$result = $connection->deepQuery($sql);
			
			$serviceCreatorEmail = $result[0]->creator_email;
			$serviceDescription = $result[0]->description;
			$serviceCategory = $result[0]->category;
			$serviceUsageText = $result[0]->usage_text;
			$serviceInsertionDate = $result[0]->insertion_date;
			$showAds = $result[0]->ads == 1; // @TODO run when deploying a service
		}

		// create a new service Object of the user type
		$userService = new $serviceName();
		$userService->serviceName = $serviceName;
		$userService->serviceDescription = $serviceDescription;
		$userService->creatorEmail = $serviceCreatorEmail;
		$userService->serviceCategory = $serviceCategory;
		$userService->serviceUsage = $serviceUsageText;
		$userService->insertionDate = $serviceInsertionDate;
		$userService->pathToService = $servicePath;
		$userService->showAds = $showAds;
		$userService->utils = $utils;

		// run the service and get a response
		if(empty($subServiceName))
		{
			$response = $userService->_main($request);
		}
		else
		{
			$subserviceFunction = "_$subServiceName";
			$response = $userService->$subserviceFunction($request);
		}

		// a service can return an array of Response or only one.
		// we always treat the response as an array
		$responses = is_array($response) ? $response : array($response);

		// clean the empty fields in the response  
		foreach($responses as $rs)
		{
			$rs->email = empty($rs->email) ? $email : $rs->email;
			$rs->subject = empty($rs->subject) ? "Respuesta del servicio $serviceName" : $rs->subject;
		}

		// create a new render
		$render = new Render();

		// render the template and echo on the screen
		if($format == "html")
		{
			$html = "";
			for ($i=0; $i<count($responses); $i++)
			{
				$html .= "<br/><center><small><b>To:</b> " . $responses[$i]->email . ". <b>Subject:</b> " . $responses[$i]->subject . "</small></center><br/>";
				$html .= $render->renderHTML($userService, $responses[$i]);
				if($i < count($responses)-1) $html .= "<br/><hr/><br/>";
			}

			$usage = nl2br(str_replace('{APRETASTE_EMAIL}', $utils->getValidEmailAddress(), $serviceUsageText));
			$html .= "<br/><hr><center><p><b>XML DEBUG</b></p><small>";
			$html .= "<p><b>Owner: </b>$serviceCreatorEmail</p>";
			$html .= "<p><b>Category: </b>$serviceCategory</p>";
			$html .= "<p><b>Description: </b>$serviceDescription</p>";
			$html .= "<p><b>Usage: </b><br/>$usage</p></small></center>";

			return $html;
		}

		// echo the json on the screen
		if($format == "json")
		{
			return $render->renderJSON($response);
		}

		// render the template email it to the user
		// only save stadistics for email requests
		if($format == "email")
		{
			// get the person, false if the person does not exist
			$person = $utils->getPerson($email);

			// if the person exist in Apretaste
			if ($person !== false)
			{
				// if the person is inactive and he/she is not trying to opt-out, re-subscribe him/her
				if( ! $person->active && $serviceName != "excluyeme") $utils->subscribeToEmailList($email);

				// update last access time to current and make person active
				$connection->deepQuery("UPDATE person SET active=1, last_access=CURRENT_TIMESTAMP WHERE email='$email'");
			}
			else // if the person accessed for the first time, insert him/her
			{
				// create a unique username
				$username = $utils->usernameFromEmail($email);

				// save the new person
				$sql = "INSERT INTO person (email, username, last_access) VALUES ('$email', '$username', CURRENT_TIMESTAMP)";
				$connection->deepQuery($sql);

			   	// check if the person was invited to use Apretaste
				$sql = "SELECT * FROM invitations WHERE email_invited='$email' AND used='0'";
				$invitations = $connection->deepQuery($sql);
				if(count($invitations)>0)
				{
					// create tickets for all the people invited. When somebody 
					// is invited by more than one person, they all get tickets
					$sql = "START TRANSACTION;";
					foreach ($invitations as $invite)
					{
						// create the query
						$sql .= "INSERT INTO ticket (email, paid) VALUES ('{$invite->email_inviter}', 0);";
						$sql .= "UPDATE person SET credit=credit+0.25 WHERE email='{$invite->email_inviter}';";
						$sql .= "UPDATE invitations SET used='1', used_time=CURRENT_TIMESTAMP WHERE invitation_id='{$invite->invitation_id}';";

						// email the invitor
						$newTicket = new Response();
						$newTicket->setResponseEmail($email);
						$newTicket->setResponseSubject("Ha ganado un ticket para nuestra Rifa");
						$newTicket->createFromText("<h1>Nuevo ticket para nuestra Rifa</h1><p>Su contacto {$invite->email_invited} ha usado Apretaste por primera vez gracias a su invitaci&oacute;n, por lo cual hemos agregado a su perfil un ticket para nuestra rifa y 25&cent; en cr&eacute;dito de Apretaste.</p><p>Muchas gracias por invitar a sus amigos, y gracias por usar Apretaste</p>");
						$newTicket->internal = true;
						$responses[] = $newTicket;
					}
					$sql .= "COMMIT;";
					$connection->deepQuery($sql);
				}

				// save details of first visit
				
				$sql = "INSERT INTO first_timers (email, source) VALUES ('$email', '$source');";
				$connection->deepQuery($sql);
				
				// send the welcome email
				$welcome = new Response();
				$welcome->setResponseEmail($email);
				$welcome->setResponseSubject("Bienvenido a Apretaste!");
				$welcome->createFromTemplate("welcome.tpl", array("email"=>$email));
				$welcome->internal = true;
				$responses[] = $welcome;

				//  add to the email list in Mail Lite
				$utils->subscribeToEmailList($email);
			}

			// get params for the email and send the response emails
			$emailSender = new Email();
			foreach($responses as $rs)
			{
				if($rs->render) // ommit default Response()
				{
					// save impressions in the database
					$ads = $rs->getAds();
					if($userService->showAds && ! empty($ads))
					{
						$sql = "";
						if( ! empty($ads[0])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[0]->id}';";
						if( ! empty($ads[1])) $sql .= "UPDATE ads SET impresions=impresions+1 WHERE id='{$ads[1]->id}';";
						$connection->deepQuery($sql);
					}

					// prepare the email variable
					$emailTo = $rs->email;
					$subject = $rs->subject;
					$images = $rs->images;
					$attachments = $rs->attachments;
					$body = $render->renderHTML($userService, $rs);

					// remove dangerous characters that may break the SQL code
					$subject = trim(preg_replace('/\'|`/', "", $subject));

					// send the response email
					$emailSender->sendEmail($emailTo, $subject, $body, $images, $attachments);
				}
			}

			// saves the openning date if the person comes from remarketing
			$connection->deepQuery("UPDATE remarketing SET opened=CURRENT_TIMESTAMP WHERE opened IS NULL AND email='$email'");

			// calculate execution time when the service stopped executing
			$currentTime = new DateTime();
			$startedTime = new DateTime($execStartTime);
			$executionTime = $currentTime->diff($startedTime)->format('%H:%I:%S');

			// get the user email domainEmail
			$emailPieces = explode("@", $email);
			$domain = $emailPieces[1];

			// get the top and bottom Ads
			$ads = isset($responses[0]->ads) ? $responses[0]->ads : array();
			$adTop = isset($ads[0]) ? $ads[0]->id : "NULL";
			$adBottom = isset($ads[1]) ? $ads[1]->id : "NULL";

			// save the logs on the utilization table
			$safeQuery = $connection->escape($query);
			$sql = "INSERT INTO utilization	(service, subservice, query, requestor, request_time, response_time, domain, ad_top, ad_bottom) VALUES ('$serviceName','$subServiceName','$safeQuery','$email','$execStartTime','$executionTime','$domain',$adTop,$adBottom)";
			$connection->deepQuery($sql);

			// return positive answer to prove the email was quequed
			return true;
		}

		// false if no action could be taken
		return false;
	}
}
