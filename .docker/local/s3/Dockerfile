FROM golang:alpine as build

RUN go install github.com/minio/mc@latest

FROM minio/minio:RELEASE.2023-09-20T22-49-55Z

COPY --from=build /go/bin/mc /usr/bin/mc
