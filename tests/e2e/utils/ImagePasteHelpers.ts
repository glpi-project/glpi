/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

import { Locator, Page, expect } from "@playwright/test";

export const TEST_IMAGE_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAEMAAABHCAYAAABcW/plAAAEJElEQVR4AeyXjVHjMBCF7WskUEmgkoRKCJUEKglUkkkluf00yPe8kfx3yd0MWcaKV7tvn6SntZF/NfHXKRBidFI0TYgRYogCYkZlhBiigJhRGSGGKCBmVEaIIQqIGZURYogCYkZlhBiigJipMg6Hw3m1WqWGLfG7MpMY2+22OZ1OqWFPVeCn4ZIYCJEXpnb23cs9iXEvix1bZ4ghCoUYIYYoIGZURoghCogZlTFVjP1+f95sNulkajln2spOqvj2FrP+ootcOOAygo53vV6fiZlv0UXuZvNnvvAf7HQ9laxYGZBC9PLy0nx8fKSTaSbkUIaPGBiwOTZ2Z2LkkAsHXDkH++vrqyEGZg4vWHLIVV4455yoi2JAClGeaO0OBuzr6yu7W4MlP5jn5+eesClQ+Mm87HIh3HPByxzI6QUWdIpizOV5e3trmFQtjxiYWrzmZ5eHBBnjtWpp3t/fa/QX/lExbMDmeDyS2NpPi43P7N7FYinXntM6PBrEzOxdcMBlzkFeBCnx4hvjtWpprRrht2HGr6oYqMpkbcD28fGxI8TGRwyMDrHb7bSb7K19ESfj+4ccE6iBA65vd4ONbyqvHwtecuGAK/POuVfF+Pz8TBOskTEgGI3bTjTsWPZh48t97pRt2i06hbaUl7mQW6Cc7CqKQQlPIQYDVkdjUrmvNj57/pshIcDQ5vIyB3LI/ZtWFMOX9tAAHqsCqA2Hx+KrNY9VLrXJ91h8S1pRjDkqe6w+FmozuSlVAY42h9djyV/SimIsIfoJOUkM3sS6GHsrjx6iMt5jlUtt8B6Lr9bmYGscc/1JjKenp14eb/yeY6Djscr18PDQy/TYXtB1/HvBhW/SLYph/6s5aI1WB7sHVmemYvgXG1hyFF+ywex2u1Lopr4khp3tW1/SLIpJ1UYnBkbjcMCVfcTx5T53fORilxoxRPQv3xL22r4kBqS+hJmMvaXTNwcTBEPDtv/rZ2Jg8OXmd9MwrfeRY/4iL4c0xOLrNXP+y3snBv/2bJEXY1PaTN4CPDZJBHzW713kalXkID4OW7mf73B4XsNO+qrNHNe+d2JAbBNsWRT2nEYOubUc+9hqS4LU8P/L3xODSbAoPqT8s07MNzBW2umjy8d8H0HAkuNjvg/GY/FlXM3O8aX3CzEg4pGxZ7tlQuyoHxwfMTBW2t0XLblDDSw55MLhedfrNR96PCotWN5jYGj67lE/9tCYc2JFMTIBE2JHWYD5WHSLjY+Y+RZd5MIBlxF0vPbiTCKYL115U8CRk5z2o35sc13lGhTjKiPchOQ2pCGG6BpihBiigJhRGSGGKCBmVEaIIQqIGZURYogCYkZlhBiigJhRGSGGKCBmVMadiyHL75tRGaJHiBFiiAJiRmWEGKKAmFEZIYYoIGZURoghCogZlSFi/AYAAP//O4yYwAAAAAZJREFUAwCnQCatff2bYwAAAABJRU5ErkJggg==';

export async function pasteImageInRichText(page: Page, getRichText: () => Promise<Locator>, expectedProperty: string): Promise<void> {
    const htmlContent = `<img src="data:image/png;base64,${TEST_IMAGE_BASE64}" />`;

    const fileuploadResponsePromise = page.waitForResponse('/ajax/fileupload.php');

    await (await getRichText()).evaluate((element, html) => {
        const dataTransfer = new DataTransfer();
        dataTransfer.setData('text/html', html);

        const pasteEvent = new ClipboardEvent('paste', {
            bubbles: true,
            cancelable: true,
            clipboardData: dataTransfer
        });

        element.dispatchEvent(pasteEvent);
    }, htmlContent);

    const fileuploadResponse = await fileuploadResponsePromise;
    const fileUploadJson = await fileuploadResponse.json();
    expect(fileuploadResponse.status()).toBe(200);
    expect(Object.keys(fileUploadJson).find(key => key === expectedProperty)).toBeDefined();
    expect(fileUploadJson[expectedProperty]).toBeInstanceOf(Array);
    expect(fileUploadJson[expectedProperty].length).toBeGreaterThan(0);
}

export async function assertPastedImageIsCorrectlyInserted(getRichText: () => Promise<Locator>): Promise<void> {
    await expect((await getRichText()).getByRole('link')).toBeVisible();
    await expect((await getRichText()).getByRole('img')).toBeVisible();
}
