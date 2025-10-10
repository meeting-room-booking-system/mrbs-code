# MRBS Cloud Run Deployment Guide

## Prerequisites
1. Google Cloud Account
2. Google Cloud CLI installed
3. Docker installed locally

## Setup Steps

### 1. Create a new GCP project
```bash
# Create new project
gcloud projects create [PROJECT_ID] --name="MRBS Meeting Rooms"

# Set as current project
gcloud config set project [PROJECT_ID]

# Enable required services
gcloud services enable \
    cloudbuild.googleapis.com \
    run.googleapis.com \
    sql-component.googleapis.com \
    sqladmin.googleapis.com
```

### 2. Create Cloud SQL Instance
```bash
# Create MySQL instance
gcloud sql instances create mrbs-db \
    --database-version=MYSQL_8_0 \
    --tier=db-f1-micro \
    --region=us-east1 \
    --root-password=[SECURE_PASSWORD]

# Create database
gcloud sql databases create mrbs --instance=mrbs-db

# Create user
gcloud sql users create mrbs-user \
    --instance=mrbs-db \
    --password=[SECURE_PASSWORD]
```

### 3. Build and Deploy
```bash
# Build the container
gcloud builds submit --tag gcr.io/[PROJECT_ID]/mrbs

# Deploy to Cloud Run
gcloud run deploy mrbs \
    --image gcr.io/[PROJECT_ID]/mrbs \
    --platform managed \
    --region us-east1 \
    --allow-unauthenticated \
    --set-env-vars="DB_HOST=[CLOUD_SQL_IP]" \
    --set-env-vars="DB_NAME=mrbs" \
    --set-env-vars="DB_USER=mrbs-user" \
    --set-env-vars="DB_PASSWORD=[DB_PASSWORD]" \
    --set-env-vars="ADMIN_EMAIL=[YOUR_EMAIL]"
```

## Post-Deployment

1. Access your MRBS instance at the provided Cloud Run URL
2. Log in with the default admin credentials
3. Change the admin password immediately
4. Create your first area and room

## Maintenance

- Monitor your Cloud SQL instance usage
- Set up regular database backups
- Configure email settings if needed
- Monitor Cloud Run logs for any issues

## Cost Optimization

- Cloud Run only charges for actual usage
- Consider setting up Cloud SQL maintenance windows
- Monitor and adjust instance sizes as needed
