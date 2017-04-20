/**
 * Apretaste Software Developer Kit
 *
 * @author @kumahacker
 * @version 1.0
 *
 * @param string pbaseUrl - Host of Apretaste REST service
 */
var apretaste = function (pbaseUrl)
{
    if (typeof(pbaseUrl) == "undefined")
        pbaseUrl = "https://apretaste.com/";

    this.baseUrl = pbaseUrl;

    /**
     * Get data via AJAX
     *
     * @author @kumahacker
     * @param string pUrl
     * @param object pParams
     * @returns object
     */
    this.getData = function(pUrl, pParams)
    {
        var receptor = {result: null};
        $.ajax({
            url: this.baseUrl + pUrl,
            method: 'GET',
            data: pParams,
            async: false,
            complete: function(res, status) {
                if (status == "success" || status == "notmodified") {
                    eval('receptor.result = ' + res.responseText +';');
                }
            }
        });
        return receptor.result;
    };

    /**
     * Get HTML via AJAX
     *
     * @author @kumahacker
     * @param string pUrl
     * @returns string
     */
    this.getHTML = function(pUrl){
        var receptor = {result: null};
        $.ajax({
            url: pUrl,
            method: 'GET',
            async: false,
            complete: function(res, status) {
                if (status == "success" || status == "notmodified") {
                    receptor.result = res.responseText;
                }
            }
        });
        return receptor.result;
    };

    /**
     * Check online URL
     *
     * @param url
     * @returns {boolean}
     */
    this.checkUrl = function(url){
        try {
            var http = new XMLHttpRequest();
            http.open('HEAD', url, false);
            http.send();
            return http.status != 404;
        }
        catch(err)
        {
            return false;
        }
    }

    /**
     * Sing up on server
     *
     * @param string email
     * @returns {Object}
     */
    this.start = function(email)
    {
        return this.getData('api/start', {email: email});
    };

    /**
     * Log on server
     *
     * @param string email
     * @param string pin
     * @returns {Object}
     */
    this.login = function(email, pin)
    {
        return this.getData('api/auth', {email: email, pin: pin});
    };

    /**
     * Logout from server
     *
     * @author @kumahacker
     * @param token
     * @returns {Object}
     */
    this.logout = function(token) {
        return this.getData('api/logout', {token: token});
    }

    /**
     * Run service
     *
     * @param string subject
     * @param string body
     * @param string token
     * @returns {Object}
     */
    this.run = function(subject, body, attachment, token)
    {
        var params = {};
        if (isset(subject)    && subject != null    && subject != '')    params.subject = subject;
        if (isset(body)       && body != null       && body != '')       params.body = body;
        if (isset(attachment) && attachment != null && attachment != '') params.attachment = attachment;
        if (isset(token)      && token != null      && token != '')      params.token = token;
        return this.getData('run/api', params);
    }
}

// ------------------ Some functions ------------------- //
/**
 * Clone isset() PHP function
 */
function isset(v){
    return typeof v !== 'undefined';
}

function is_email(emailAddress) {
    var sQtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
    var sDtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
    var sAtom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
    var sQuotedPair = '\\x5c[\\x00-\\x7f]';
    var sDomainLiteral = '\\x5b(' + sDtext + '|' + sQuotedPair + ')*\\x5d';
    var sQuotedString = '\\x22(' + sQtext + '|' + sQuotedPair + ')*\\x22';
    var sDomain_ref = sAtom;
    var sSubDomain = '(' + sDomain_ref + '|' + sDomainLiteral + ')';
    var sWord = '(' + sAtom + '|' + sQuotedString + ')';
    var sDomain = sSubDomain + '(\\x2e' + sSubDomain + ')*';
    var sLocalPart = sWord + '(\\x2e' + sWord + ')*';
    var sAddrSpec = sLocalPart + '\\x40' + sDomain; // complete RFC822 email address spec
    var sValidEmail = '^' + sAddrSpec + '$'; // as whole string

    var reValidEmail = new RegExp(sValidEmail);

    return reValidEmail.test(emailAddress);
}

function str_replace(search,replace,subject,count){
    var i=0,j=0,temp='',repl='',sl=0,fl=0,f=[].concat(search),r=[].concat(replace),s=subject,ra=r instanceof Array,sa=s instanceof Array;
    s=[].concat(s);
    if(count){
        this.window[count]=0;
    }
    for(i=0,sl=s.length;i<sl;i++){
        if(s[i]===''){
            continue;
        }
        for(j=0,fl=f.length;j<fl;j++){
            temp=s[i]+'';
            repl=ra?(r[j]!==undefined?r[j]:''):r[0];
            s[i]=(temp).split(f[j]).join(repl);
            if(count&&s[i]!==temp){
                this.window[count]+=(temp.length-s[i].length)/f[j].length;
            }
        }
        }
return sa?s:s[0];
}

function replace_tags(tpl, data, prefix, suffix){

	if (!isset(prefix))
		prefix = '';

	if (!isset(suffix))
		suffix = '';

	var html = tpl;

	for(var tag in data)
		html = str_replace('{{ ' + prefix + tag + suffix +' }}', data[tag], html);

	return html;
}