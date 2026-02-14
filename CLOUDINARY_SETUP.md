# PIJP Gas Ordering - Cloudinary Setup

## Problem Solved
When hosting on Render (or similar platforms), uploaded images disappear after server restarts because the filesystem is **ephemeral**. Cloudinary provides persistent cloud storage for images.

## Setup Instructions

### 1. Create Cloudinary Account
1. Go to [https://cloudinary.com/users/register_free](https://cloudinary.com/users/register_free)
2. Sign up for a free account (no credit card required)
3. Verify your email

### 2. Get Your Credentials
1. Log in to [Cloudinary Console](https://console.cloudinary.com/)
2. On your dashboard, you'll see:
   - **Cloud Name**: e.g., `dxyz123abc`
   - **API Key**: e.g., `123456789012345`
   - **API Secret**: e.g., `abcdefghijklmnopqrstuvwxyz123456`
3. Copy these values

### 3. Configure Locally (Development)
For testing on Laragon:
1. Open `c:\laragon\www\PIJP\.env.cloudinary`
2. Replace the placeholders with your actual credentials
3. Restart Laragon
4. Test by uploading a gas tank image in Admin > Manage Stock

### 4. Configure on Render (Production)
1. Go to your Render dashboard
2. Select your PIJP service
3. Navigate to **Environment** tab
4. Add these environment variables:
   ```
   CLOUDINARY_CLOUD_NAME = your_cloud_name_here
   CLOUDINARY_API_KEY = your_api_key_here
   CLOUDINARY_API_SECRET = your_api_secret_here
   ```
5. Save changes (Render will automatically redeploy)

## How It Works

- When Cloudinary is configured (credentials present), images upload to Cloudinary cloud storage
- Image URLs are stored in the `gas_tanks.image_path` column as full HTTPS URLs
- If credentials are missing, the system falls back to local storage (for development only)

## After Setup

1. **Re-upload all product images** through Admin > Manage Stock
2. Old local images won't work anymore - they were deleted on Render restarts
3. New images will persist permanently on Cloudinary

## Free Tier Limits
- 25 GB storage
- 25 GB bandwidth/month
- 25k transformations/month
- More than enough for this application

## Troubleshooting

**Images not uploading?**
- Check that all 3 environment variables are set correctly
- Verify credentials are copied exactly (no extra spaces)
- Check Render logs for error messages

**Still seeing old local paths?**
- Make sure to upload new images after Cloudinary setup
- Update existing tank records by re-uploading their images

## Technical Details

- Images are stored in the `pijp/tanks/` folder in your Cloudinary account
- Each image gets a unique public ID like `tank_abc123def456`
- URLs look like: `https://res.cloudinary.com/your_cloud_name/image/upload/v123456789/pijp/tanks/tank_abc123def456.jpg`
- Images are served via Cloudinary's global CDN (fast delivery worldwide)
