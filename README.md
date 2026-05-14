# SEO & Meta

Add SEO meta tags, Open Graph images, and dynamic server share cards to your Pterodactyl panel. Fully editable from the admin dashboard with a live preview.

## Features

### SEO & Open Graph Tags
- **Site Title & Description** — Set custom meta title and description for search engines
- **Open Graph Image** — Upload a custom image shown when your panel link is shared
- **Theme Color** — Custom accent color for Discord/Twitter embeds
- **Twitter Card** — Choose between large image or summary card types
- **Favicon** — Upload a custom browser tab icon

### Dynamic Server Share Cards
When a user shares a server link from your panel on Discord, Twitter, or Facebook:
- A **custom image** is generated dynamically using PHP GD
- **Black gradient background** with subtle grid pattern
- **Server name** displayed prominently at the center
- **Your hosting logo** and company name on the bottom left
- Fully branded — your hosting identity on every share

### Live Preview
- **Google Search** preview — see exactly how your panel appears in search results
- **Discord Embed** preview — live preview of the Discord embed card
- **Twitter/X Card** preview — see the Twitter card in real-time
- **Server Share Card** preview — preview the dynamic server image
- All previews update instantly as you type

## Installation

1. Install via `blueprint -install seometa.blueprint`
2. Navigate to **Admin → Extensions → SEO & Meta**
3. Fill in your title, description, upload images, and save

## Requirements

- Pterodactyl Panel 1.x
- Blueprint Framework (beta-2026-01)
- PHP GD extension (for dynamic server images)

## Support

- Discord: [azioncloud.com/discord](https://azioncloud.com/discord)
- Website: [azioncloud.com](https://azioncloud.com)
