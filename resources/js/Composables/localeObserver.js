import {ref} from "vue";

export function useLangObserver() {
    const locale = ref(document.documentElement.lang); // Initially set to current <html> lang
    let observer;

    // Function to observe changes to the <html> lang attribute
    const observeLangChange = () => {
        const targetNode = document.documentElement;

        // Create a MutationObserver to watch for attribute changes
        observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'lang') {
                    locale.value = document.documentElement.lang; // Update locale when lang changes
                }
            });
        });

        // Start observing the <html> element for changes to the lang attribute
        observer.observe(targetNode, {
            attributes: true, // Observe changes to attributes
            attributeFilter: ['lang'], // Only listen for changes to the 'lang' attribute
        });
    };

    // Start the observer immediately
    observeLangChange();

    // Return the locale ref, and a function to disconnect the observer if needed
    return {
        locale,
        disconnectObserver: () => observer?.disconnect()
    };
}
