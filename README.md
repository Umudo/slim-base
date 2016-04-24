**Basic Slim Skeleton App**

Very basic slim framework skeleton. Supports MVC. Basic settings can be found in config/settings.php file.

If you need to use jobqueue, you need the add cron/jobQueueConsumer.php as a cron. Suggested cron config is to run it every minute.

Extensions required are phpredis and mongodb driver. Requires php 7.0 to run.