#!/bin/sh

echo "S3 setup start!"

aws --endpoint-url http://localstack:4566 s3 mb s3://sample-bucket
aws s3 ls --endpoint-url=http://localstack:4566

echo "S3 setup Done!"
