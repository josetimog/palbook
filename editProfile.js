document.addEventListener('DOMContentLoaded', function() {
    // Declare imageId variable outside of the event listener function
    let imageId;

    // Get all images with the imageChoice class
    const images = document.querySelectorAll('.imageChoice');

    // Add a click event listener to each image
    images.forEach(function(image) {
        image.addEventListener('click', function() {
            imageId = this.dataset.image_id;
            console.log(imageId);

            images.forEach(function(img) {
                img.classList.remove('selected');
            });

            this.classList.toggle('selected');
        });
    });

    const chooseImageBtn = document.getElementById('chooseImageBtn');
    chooseImageBtn.addEventListener('click', function() {
        const form = document.getElementById('profilePictureForm');

        // Create a hidden input
        let hiddenInput = form.querySelector('input[name="image_id"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'image_id';
            hiddenInput.value = imageId;
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = imageId;
        form.submit();
    });
});
