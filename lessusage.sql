SELECT requestor, count(service) as part, 12 as total FROM (SELECT requestor, service FROM utilization WHERE service <> 'rememberme' GROUP BY service) subq1 GROUP BY service
LESS USAGE REMARKETING (0)
EXECUTION TIME: 1 seconds

