<template>
  <div class="mailbox-page">
    <div v-if="loadingSettings" class="mailbox-loading mailbox-loading-page">
      <div class="spinner-border text-primary" role="status"></div>
      <span>Preparation de la messagerie...</span>
    </div>

    <section v-else-if="showSettingsPanel" class="mailbox-settings">
      <div class="mailbox-settings-head">
        <div>
          <span class="mailbox-kicker">Messagerie professionnelle</span>
          <h1>Connexion de la boite mail</h1>
          <p>Les identifiants IMAP sont chiffres dans l'application.</p>
        </div>
        <button
          v-if="settings?.has_credentials"
          type="button"
          class="mailbox-icon-btn"
          title="Fermer"
          @click="settingsOpen = false"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form class="mailbox-settings-form" @submit.prevent="saveMailboxSettings">
        <label>
          <span>Email professionnel</span>
          <input v-model.trim="settingsForm.email" type="email" autocomplete="email" required>
        </label>
        <label>
          <span>Utilisateur IMAP</span>
          <input v-model.trim="settingsForm.username" type="text" autocomplete="username" required>
        </label>
        <label>
          <span>Mot de passe</span>
          <input
            v-model="settingsForm.password"
            type="password"
            autocomplete="current-password"
            :placeholder="settings?.has_credentials ? 'Laisser vide pour conserver' : ''"
            :required="!settings?.has_credentials"
          >
        </label>
        <label>
          <span>Serveur IMAP</span>
          <input v-model.trim="settingsForm.imap_host" type="text" required>
        </label>
        <label>
          <span>Port</span>
          <input v-model.number="settingsForm.imap_port" type="number" min="1" max="65535" required>
        </label>
        <label>
          <span>Securite</span>
          <select v-model="settingsForm.imap_encryption" required>
            <option value="ssl">SSL</option>
            <option value="tls">TLS</option>
            <option value="none">Aucune</option>
          </select>
        </label>

        <div class="mailbox-settings-signature">
          <label>
            <span>Signature automatique</span>
            <textarea v-model="settingsForm.signature" rows="8" maxlength="5000"></textarea>
          </label>
          <aside class="mailbox-signature-preview">
            <div class="mailbox-signature-preview-head">
              <span>Apercu</span>
              <button type="button" @click="resetSignatureFromDefault">
                <i class="fas fa-wand-magic-sparkles"></i>
                Regenerer
              </button>
            </div>
            <div class="mailbox-signature-card">
              <div v-if="signaturePreview.greeting" class="mailbox-signature-greeting">{{ signaturePreview.greeting }}</div>
              <div class="mailbox-signature-name">{{ signaturePreview.name }}</div>
              <div v-if="signaturePreview.role" class="mailbox-signature-role">{{ signaturePreview.role }}</div>
              <div v-for="line in signaturePreview.details" :key="line" class="mailbox-signature-detail">{{ line }}</div>
            </div>
          </aside>
        </div>

        <div v-if="settingsError" class="mailbox-alert mailbox-alert-danger">
          <i class="fas fa-triangle-exclamation"></i>
          {{ settingsError }}
        </div>

        <div class="mailbox-settings-actions">
          <button type="submit" class="mailbox-primary-btn" :disabled="savingSettings">
            <span v-if="savingSettings" class="spinner-border spinner-border-sm"></span>
            <i v-else class="fas fa-plug"></i>
            Connecter
          </button>
        </div>
      </form>
    </section>

    <section v-else class="outlook-shell">
      <div v-if="mobileMenuOpen" class="mailbox-mobile-menu-backdrop" @click.self="closeMobileMenu">
        <aside class="mailbox-mobile-menu" aria-label="Dossiers messagerie mobile">
          <div class="mailbox-mobile-menu-rail">
            <button type="button" class="mailbox-mobile-rail-btn active" title="Boite" @click="openInbox">
              <i class="fas fa-globe"></i>
            </button>
            <button type="button" class="mailbox-mobile-rail-avatar" title="Compte" @click="openInbox">
              {{ accountInitial }}
            </button>
            <button type="button" class="mailbox-mobile-rail-btn" title="Nouveau mail" @click="openComposer">
              <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="mailbox-mobile-rail-btn mt-auto" title="Parametres" @click="settingsOpen = true; closeMobileMenu()">
              <i class="fas fa-cog"></i>
            </button>
          </div>

          <div class="mailbox-mobile-menu-panel">
            <div class="mailbox-mobile-menu-head">
              <div>
                <span>IMAP</span>
                <strong>{{ settings?.account_email }}</strong>
              </div>
              <button type="button" class="mailbox-mobile-menu-close" title="Fermer" @click="closeMobileMenu">
                <i class="fas fa-times"></i>
              </button>
            </div>

            <nav class="mailbox-mobile-menu-nav" aria-label="Navigation messagerie mobile">
              <div class="mailbox-mobile-nav-title">Essentiel</div>
              <button type="button" class="mailbox-folder-btn" :class="{ active: isInboxActive }" @click="openInbox">
                <i class="fas fa-inbox"></i>
                <span>Boite de reception</span>
                <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
              </button>
              <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'unread' }" @click="openFilter('unread')">
                <i class="fas fa-envelope"></i>
                <span>Non lus</span>
                <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
              </button>
              <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'flagged' }" @click="openFilter('flagged')">
                <i class="fas fa-star"></i>
                <span>Avec etoile</span>
              </button>
              <button
                v-for="folder in essentialFolders"
                :key="`mobile-essential-${folder.name}`"
                type="button"
                class="mailbox-folder-btn"
                :class="{ active: activeFilter === '' && activeFolder === folder.name }"
                @click="openFolder(folder)"
              >
                <i class="fas" :class="folderIcon(folder.type)"></i>
                <span>{{ folder.label }}</span>
                <b v-if="folder.unread">{{ folder.unread }}</b>
              </button>

              <template v-if="secondaryFolders.length">
                <div class="mailbox-mobile-nav-title">Autres dossiers</div>
                <button
                  v-for="folder in visibleSecondaryFolders"
                  :key="`mobile-secondary-${folder.name}`"
                  type="button"
                  class="mailbox-folder-btn"
                  :class="{ active: activeFilter === '' && activeFolder === folder.name }"
                  @click="openFolder(folder)"
                >
                  <i class="fas" :class="folderIcon(folder.type)"></i>
                  <span>{{ folder.label }}</span>
                  <b v-if="folder.unread">{{ folder.unread }}</b>
                </button>
                <button
                  v-if="secondaryFolders.length > 5"
                  type="button"
                  class="mailbox-folder-btn mailbox-folder-more"
                  @click="sidebarFoldersExpanded = !sidebarFoldersExpanded"
                >
                  <i class="fas" :class="sidebarFoldersExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                  <span>{{ sidebarFoldersExpanded ? 'Reduire' : `Afficher ${secondaryFolders.length - 5} autres` }}</span>
                </button>
              </template>
            </nav>
          </div>
        </aside>
      </div>

      <aside class="mailbox-sidebar">
        <div class="mailbox-account">
          <div class="mailbox-account-avatar">{{ accountInitial }}</div>
          <div>
            <span>Compte</span>
            <strong>{{ settings?.account_email }}</strong>
          </div>
        </div>

        <button type="button" class="mailbox-compose-btn" @click="openComposer">
          <i class="fas fa-pen"></i>
          Nouveau mail
        </button>

        <nav class="mailbox-folder-nav" aria-label="Navigation messagerie">
          <div class="mailbox-nav-title">Essentiel</div>
          <button type="button" class="mailbox-folder-btn" :class="{ active: isInboxActive }" @click="openInbox">
            <i class="fas fa-inbox"></i>
            <span>Boite de reception</span>
            <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
          </button>
          <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'unread' }" @click="openFilter('unread')">
            <i class="fas fa-envelope"></i>
            <span>Non lus</span>
            <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
          </button>
          <button type="button" class="mailbox-folder-btn" :class="{ active: activeFilter === 'flagged' }" @click="openFilter('flagged')">
            <i class="fas fa-star"></i>
            <span>Avec etoile</span>
          </button>
          <button
            v-for="folder in essentialFolders"
            :key="`essential-${folder.name}`"
            type="button"
            class="mailbox-folder-btn"
            :class="{ active: activeFilter === '' && activeFolder === folder.name }"
            @click="openFolder(folder)"
          >
            <i class="fas" :class="folderIcon(folder.type)"></i>
            <span>{{ folder.label }}</span>
            <b v-if="folder.unread">{{ folder.unread }}</b>
          </button>

          <template v-if="secondaryFolders.length">
            <div class="mailbox-nav-title">Autres dossiers</div>
            <button
              v-for="folder in visibleSecondaryFolders"
              :key="`secondary-${folder.name}`"
              type="button"
              class="mailbox-folder-btn"
              :class="{ active: activeFilter === '' && activeFolder === folder.name }"
              @click="openFolder(folder)"
            >
              <i class="fas" :class="folderIcon(folder.type)"></i>
              <span>{{ folder.label }}</span>
              <b v-if="folder.unread">{{ folder.unread }}</b>
            </button>
            <button
              v-if="secondaryFolders.length > 5"
              type="button"
              class="mailbox-folder-btn mailbox-folder-more"
              @click="sidebarFoldersExpanded = !sidebarFoldersExpanded"
            >
              <i class="fas" :class="sidebarFoldersExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              <span>{{ sidebarFoldersExpanded ? 'Reduire' : `Afficher ${secondaryFolders.length - 5} autres` }}</span>
            </button>
          </template>
        </nav>

        <div class="mailbox-side-card">
          <span>Connecte via IMAP</span>
          <strong>{{ folders.length }} dossier(s)</strong>
        </div>
      </aside>

      <main class="mailbox-main" :class="{ 'mailbox-main-reading': isMobileMailbox && selectedUid }">
        <header class="mailbox-mobile-header">
          <button type="button" class="mailbox-mobile-avatar-btn" title="Dossiers" @click="openMobileMenu">
            <span>{{ accountInitial }}</span>
          </button>
          <div class="mailbox-mobile-heading">
            <span>{{ activeSubtitle }}</span>
            <h1>{{ selectedMessage?.subject || activeTitle }}</h1>
          </div>
          <button type="button" class="mailbox-mobile-action" title="Actualiser" @click="loadMessages(meta.current_page || 1)">
            <i class="fas fa-rotate-right"></i>
          </button>
          <button type="button" class="mailbox-mobile-action" title="Rechercher" @click="toggleMobileSearch">
            <i class="fas fa-search"></i>
          </button>
        </header>

        <div class="mailbox-mobile-filterbar">
          <div class="mailbox-mobile-pills">
            <button type="button" :class="{ active: activeFilter === '' }" @click="openInbox">Prioritaire</button>
            <button type="button" :class="{ active: activeFilter === 'unread' }" @click="openFilter('unread')">
              Non lus
              <b v-if="inboxFolder?.unread">{{ inboxFolder.unread }}</b>
            </button>
          </div>
          <button type="button" class="mailbox-mobile-filter" @click="toggleMobileSearch">
            <i class="fas fa-filter"></i>
            Filtrer
          </button>
        </div>

        <div v-if="mobileSearchOpen" class="mailbox-mobile-search">
          <div class="mailbox-search-field">
            <i class="fas fa-search"></i>
            <input
              ref="mobileSearchInput"
              v-model.trim="search"
              type="search"
              placeholder="Rechercher dans les mails..."
              @input="onSearch"
              @keydown.enter.prevent="submitSearch"
            >
            <button v-if="search" type="button" title="Effacer" @click="clearSearch">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <select v-model="searchScope" class="mailbox-search-scope" @change="submitSearch">
            <option value="all">Tout</option>
            <option value="subject">Objet</option>
            <option value="from">Expediteur</option>
            <option value="recipient">Destinataire</option>
          </select>
        </div>

        <header class="mailbox-topbar">
          <div>
            <span class="mailbox-kicker">{{ activeSubtitle }}</span>
            <h1>{{ activeTitle }}</h1>
          </div>
          <div class="mailbox-search">
            <div class="mailbox-search-field">
              <i class="fas fa-search"></i>
              <input
                v-model.trim="search"
                type="search"
                placeholder="Rechercher dans les mails..."
                @input="onSearch"
                @keydown.enter.prevent="submitSearch"
              >
              <button v-if="search" type="button" title="Effacer" @click="clearSearch">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <select v-model="searchScope" class="mailbox-search-scope" @change="submitSearch">
              <option value="all">Tout</option>
              <option value="subject">Objet</option>
              <option value="from">Expediteur</option>
              <option value="recipient">Destinataire</option>
            </select>
          </div>
        </header>

        <div class="mailbox-commandbar">
          <button type="button" class="mailbox-command primary" @click="openComposer">
            <i class="fas fa-plus"></i>
            Nouveau
          </button>
          <div class="mailbox-selection-status" :class="{ active: selectedBulkCount }">
            <i class="fas" :class="selectedBulkCount ? 'fa-square-check' : 'fa-envelope-open-text'"></i>
            <span>{{ selectedBulkCount ? `${selectedBulkCount} selectionne(s)` : activeTitle }}</span>
            <button v-if="selectedBulkCount" type="button" @click="clearBulkSelection">Annuler</button>
          </div>
          <button type="button" class="mailbox-command" @click="loadMessages(meta.current_page || 1)">
            <i class="fas fa-rotate-right"></i>
            Actualiser
          </button>
          <button type="button" class="mailbox-command" :disabled="bulkProcessing || !hasActionTarget" @click="toggleReadAction">
            <i class="fas" :class="actionMessages.some(message => message.unread) ? 'fa-envelope-open' : 'fa-envelope'"></i>
            {{ readActionLabel }}
          </button>
          <button type="button" class="mailbox-command" :disabled="bulkProcessing || !hasActionTarget" @click="toggleFlagAction">
            <i class="fas fa-star"></i>
            {{ flagActionLabel }}
          </button>
          <button type="button" class="mailbox-command" :disabled="bulkProcessing || !hasActionTarget || !archiveFolder" @click="archiveSelected">
            <i class="fas fa-box-archive"></i>
            Archiver
          </button>
          <button type="button" class="mailbox-command danger" :disabled="bulkProcessing || !hasActionTarget" @click="deleteActionTargets">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
          <div class="mailbox-move">
            <select v-model="targetFolder" :disabled="bulkProcessing || !hasActionTarget || !moveTargets.length">
              <option value="">Deplacer vers...</option>
              <option v-for="folder in moveTargets" :key="folder.name" :value="folder.name">{{ folder.label }}</option>
            </select>
            <button type="button" class="mailbox-command" :disabled="bulkProcessing || !hasActionTarget || !targetFolder" @click="moveSelectedTo(targetFolder)">
              <i class="fas fa-folder-tree"></i>
            </button>
          </div>
          <button type="button" class="mailbox-command settings" @click="settingsOpen = true">
            <i class="fas fa-cog"></i>
          </button>
        </div>

        <div v-if="messagesError" class="mailbox-alert mailbox-alert-danger mailbox-alert-inline">
          <i class="fas fa-triangle-exclamation"></i>
          {{ messagesError }}
        </div>

        <div class="mailbox-content" :class="{ 'mailbox-content-reading': isMobileMailbox && selectedUid }">
          <section class="mailbox-list-pane">
            <div class="mailbox-list-head">
              <div class="mailbox-list-head-main">
                <button
                  type="button"
                  class="mailbox-select-toggle"
                  :class="{ active: allPageSelected || somePageSelected }"
                  :title="allPageSelected ? 'Tout deselectionner' : 'Selectionner la page'"
                  @click="togglePageSelection"
                >
                  <i class="fas" :class="allPageSelected ? 'fa-square-check' : (somePageSelected ? 'fa-square-minus' : 'fa-square')"></i>
                </button>
                <strong>{{ meta.total || 0 }} message(s)</strong>
              </div>
              <span>{{ meta.unread_count || 0 }} non lu(s)</span>
            </div>

            <div v-if="loadingMessages" class="mailbox-loading mailbox-loading-panel">
              <div class="spinner-border text-primary" role="status"></div>
              <span>Chargement des mails...</span>
            </div>

            <div v-else-if="!messages.length" class="mailbox-empty">
              <i class="fas fa-inbox"></i>
              <h3>Aucun mail trouve</h3>
              <p>{{ search ? 'Essayez une autre recherche.' : 'Ce dossier est vide.' }}</p>
            </div>

            <div v-else class="mailbox-message-list">
              <article
                v-for="message in messages"
                :key="message.uid"
                class="mailbox-message-item"
                :class="{ active: selectedUid === message.uid, unread: message.unread, selected: isBulkSelected(message.uid) }"
                role="button"
                tabindex="0"
                @click="selectMessage(message)"
                @keydown.enter.prevent="selectMessage(message)"
                @keydown.space.prevent="selectMessage(message)"
              >
                <button
                  type="button"
                  class="mailbox-message-check"
                  :class="{ active: isBulkSelected(message.uid) }"
                  :title="isBulkSelected(message.uid) ? 'Retirer de la selection' : 'Selectionner ce mail'"
                  @click.stop="toggleMessageSelection(message)"
                >
                  <i class="fas" :class="isBulkSelected(message.uid) ? 'fa-square-check' : 'fa-square'"></i>
                </button>
                <span class="mailbox-avatar">{{ senderInitial(message.from) }}</span>
                <span class="mailbox-message-content">
                  <span class="mailbox-message-top">
                    <strong>{{ cleanSender(message.from) || 'Expediteur' }}</strong>
                    <time>{{ formatDate(message.date, true) }}</time>
                  </span>
                  <span class="mailbox-subject">{{ message.subject }}</span>
                  <span class="mailbox-message-meta">
                    <i v-if="message.flagged" class="fas fa-star"></i>
                    <i v-if="message.answered" class="fas fa-reply"></i>
                    <i v-if="message.has_attachments" class="fas fa-paperclip"></i>
                    {{ formatSize(message.size) }}
                  </span>
                </span>
              </article>
            </div>

            <nav v-if="meta.last_page > 1" class="mailbox-pagination" aria-label="Pagination mails">
              <button type="button" :disabled="meta.current_page <= 1" @click="loadMessages(meta.current_page - 1)">
                <i class="fas fa-chevron-left"></i>
              </button>
              <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
              <button type="button" :disabled="meta.current_page >= meta.last_page" @click="loadMessages(meta.current_page + 1)">
                <i class="fas fa-chevron-right"></i>
              </button>
            </nav>
          </section>

          <article class="mailbox-reader">
            <div v-if="selectedLoading" class="mailbox-loading mailbox-loading-panel">
              <div class="spinner-border text-primary" role="status"></div>
              <span>Ouverture du mail...</span>
            </div>

            <template v-else-if="selectedMessage">
              <header class="mailbox-reader-head">
                <div class="mailbox-reader-main">
                  <button type="button" class="mailbox-reader-back" @click="resetSelection">
                    <i class="fas fa-arrow-left"></i>
                    Messages
                  </button>
                  <h2>{{ selectedMessage.subject }}</h2>
                  <div class="mailbox-reader-meta mailbox-reader-meta-compact">
                    <span class="mailbox-reader-meta-line compact">
                      <b>De</b>
                      <span>{{ selectedMessage.from }}</span>
                    </span>
                    <span class="mailbox-reader-meta-line compact">
                      <b>A</b>
                      <span>{{ compactAddressLine(selectedMessage.to_addresses, selectedMessage.to) || '-' }}</span>
                    </span>
                    <button
                      v-if="hasRecipientDetails(selectedMessage)"
                      type="button"
                      class="mailbox-recipient-toggle"
                      @click="recipientDetailsOpen = !recipientDetailsOpen"
                    >
                      {{ recipientDetailsOpen ? 'Masquer les détails' : 'Détails' }}
                      <i class="fas" :class="recipientDetailsOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </button>
                    <time class="mailbox-reader-date">{{ formatDate(selectedMessage.date) }}</time>
                  </div>
                  <div v-if="recipientDetailsOpen" class="mailbox-recipient-details">
                    <span class="mailbox-reader-meta-line">
                      <b>A</b>
                      <span>{{ addressLine(selectedMessage.to_addresses, selectedMessage.to) || '-' }}</span>
                    </span>
                    <span v-if="addressLine(selectedMessage.cc_addresses, selectedMessage.cc)" class="mailbox-reader-meta-line">
                      <b>CC</b>
                      <span>{{ addressLine(selectedMessage.cc_addresses, selectedMessage.cc) }}</span>
                    </span>
                    <span v-if="addressLine(selectedMessage.bcc_addresses, selectedMessage.bcc)" class="mailbox-reader-meta-line">
                      <b>CCI</b>
                      <span>{{ addressLine(selectedMessage.bcc_addresses, selectedMessage.bcc) }}</span>
                    </span>
                  </div>
                </div>
                <div class="mailbox-reader-actions">
                  <button type="button" class="mailbox-icon-btn" title="Repondre" @click="replyToSelected">
                    <i class="fas fa-reply"></i>
                  </button>
                  <button type="button" class="mailbox-icon-btn" title="Transferer" @click="forwardSelected">
                    <i class="fas fa-share"></i>
                  </button>
                  <button type="button" class="mailbox-icon-btn" title="Lu / non lu" @click="toggleRead(selectedMessage)">
                    <i class="fas" :class="selectedMessage.unread ? 'fa-envelope-open' : 'fa-envelope'"></i>
                  </button>
                </div>
              </header>

              <div class="mailbox-reader-tools">
                <button
                  type="button"
                  :title="selectedMessage.flagged ? 'Retirer l etoile' : 'Marquer avec etoile'"
                  :aria-label="selectedMessage.flagged ? 'Retirer l etoile' : 'Marquer avec etoile'"
                  @click="toggleFlag(selectedMessage)"
                >
                  <i class="fas fa-star"></i>
                  {{ selectedMessage.flagged ? 'Retirer l etoile' : 'Marquer avec etoile' }}
                </button>
                <button
                  type="button"
                  title="Archiver"
                  aria-label="Archiver"
                  :disabled="!archiveFolder || bulkProcessing"
                  @click="archiveMessage(selectedMessage)"
                >
                  <i class="fas fa-box-archive"></i>
                  Archiver
                </button>
                <button
                  type="button"
                  class="danger"
                  title="Supprimer"
                  aria-label="Supprimer"
                  :disabled="bulkProcessing"
                  @click="deleteMessage(selectedMessage.uid)"
                >
                  <i class="fas fa-trash"></i>
                  Supprimer
                </button>
              </div>

              <div v-if="selectedMessage.attachments?.length" class="mailbox-reader-attachments">
                <strong>
                  <i class="fas fa-paperclip"></i>
                  Pieces jointes
                </strong>
                <div class="mailbox-reader-attachment-list">
                  <button
                    v-for="attachment in selectedMessage.attachments"
                    :key="attachment.part"
                    type="button"
                    class="mailbox-reader-attachment"
                    @click="downloadAttachment(attachment)"
                  >
                    <i class="fas" :class="attachmentIcon(attachment.mime)"></i>
                    <span>
                      <b>{{ attachment.name }}</b>
                      <small>{{ formatSize(attachment.size) }} - {{ attachment.mime || 'fichier' }}</small>
                    </span>
                    <i class="fas fa-download"></i>
                  </button>
                </div>
              </div>

              <div class="mailbox-reader-body">
                <template v-if="readerBodyBlocks.length">
                  <section
                    v-for="(block, index) in readerBodyBlocks"
                    :key="`mail-body-${index}`"
                    class="mailbox-body-block"
                    :class="`mailbox-body-${block.type}`"
                  >
                    <div v-for="(line, lineIndex) in block.lines" :key="`mail-body-${index}-${lineIndex}`" v-html="line"></div>
                  </section>
                </template>
                <p v-else class="mailbox-body-empty">Aucun contenu lisible.</p>
              </div>
            </template>

            <div v-else class="mailbox-reader-empty">
              <i class="fas fa-envelope-open-text"></i>
              <h3>Selectionnez un message</h3>
              <p>{{ activeTitle }}</p>
            </div>
          </article>
        </div>
      </main>
    </section>

    <nav v-if="!showSettingsPanel" class="mailbox-mobile-tabbar" aria-label="Navigation messagerie">
      <button type="button" class="active" @click="openInbox">
        <i class="fas fa-envelope"></i>
        <span>Courrier</span>
      </button>
      <button type="button" @click="openMobileMenu">
        <i class="fas fa-folder"></i>
        <span>Dossiers</span>
      </button>
      <router-link :to="{ name: 'dashboard' }">
        <i class="fas fa-house"></i>
        <span>Accueil</span>
      </router-link>
    </nav>

    <button
      v-if="!showSettingsPanel && !composerOpen"
      type="button"
      class="mailbox-mobile-compose-fab"
      title="Nouveau mail"
      @click="openComposer"
    >
      <i class="fas fa-pen"></i>
    </button>

    <div v-if="composerOpen && !showSettingsPanel" class="mailbox-compose-backdrop" @click.self="composerOpen = false">
      <section class="mailbox-compose-drawer">
        <div class="mailbox-compose-head">
          <div>
            <span class="mailbox-kicker">Depuis {{ settings?.account_email }}</span>
            <h2>Nouveau mail</h2>
          </div>
          <button type="button" class="mailbox-icon-btn" title="Fermer" @click="composerOpen = false">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form class="mailbox-compose-form" @submit.prevent="sendMail">
          <div class="mailbox-compose-recipients">
            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">A</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('to')">
                <span v-for="recipient in composeForm.to" :key="`to-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('to', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="toInput"
                  v-model.trim="recipientDraft.to"
                  type="text"
                  placeholder="Nom, poste, groupe ou email"
                  @focus="activeRecipientField = 'to'"
                  @input="activeRecipientField = 'to'"
                  @keydown.enter.prevent="commitRecipient('to')"
                  @keydown.tab.prevent="commitRecipient('to')"
                  @keydown.backspace="removeLastRecipient('to', $event)"
                >
              </div>
            </div>

            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">CC</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('cc')">
                <span v-for="recipient in composeForm.cc" :key="`cc-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('cc', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="ccInput"
                  v-model.trim="recipientDraft.cc"
                  type="text"
                  placeholder="Copie"
                  @focus="activeRecipientField = 'cc'"
                  @input="activeRecipientField = 'cc'"
                  @keydown.enter.prevent="commitRecipient('cc')"
                  @keydown.tab.prevent="commitRecipient('cc')"
                  @keydown.backspace="removeLastRecipient('cc', $event)"
                >
              </div>
            </div>

            <div class="mailbox-recipient-row">
              <span class="mailbox-recipient-label">CCI</span>
              <div class="mailbox-recipient-box" @click="focusRecipient('bcc')">
                <span v-for="recipient in composeForm.bcc" :key="`bcc-${recipient.email}`" class="mailbox-recipient-chip">
                  {{ recipient.name || recipient.email }}
                  <button type="button" title="Retirer" @click.stop="removeRecipient('bcc', recipient.email)">
                    <i class="fas fa-times"></i>
                  </button>
                </span>
                <input
                  ref="bccInput"
                  v-model.trim="recipientDraft.bcc"
                  type="text"
                  placeholder="Copie cachee"
                  @focus="activeRecipientField = 'bcc'"
                  @input="activeRecipientField = 'bcc'"
                  @keydown.enter.prevent="commitRecipient('bcc')"
                  @keydown.tab.prevent="commitRecipient('bcc')"
                  @keydown.backspace="removeLastRecipient('bcc', $event)"
                >
              </div>
            </div>
          </div>

          <div class="mailbox-smart-panel">
            <div class="mailbox-smart-head">
              <strong>Suggestions intelligentes</strong>
              <span>{{ loadingContacts ? 'Chargement...' : `${contactOptions.length} contact(s)` }}</span>
            </div>
            <div class="mailbox-smart-groups">
              <button
                v-for="group in smartGroups"
                :key="group.key"
                type="button"
                :disabled="!group.contacts.length"
                @click="addGroupRecipients(group)"
              >
                <i class="fas" :class="group.icon"></i>
                <span>{{ group.label }}</span>
                <b>{{ group.contacts.length }}</b>
              </button>
            </div>
            <div v-if="filteredContactSuggestions.length" class="mailbox-contact-suggestions">
              <button
                v-for="contact in filteredContactSuggestions"
                :key="`${activeRecipientField}-${contact.email}`"
                type="button"
                @click="addRecipient(activeRecipientField, contact)"
              >
                <span class="mailbox-contact-avatar">{{ contactInitial(contact) }}</span>
                <span>
                  <strong>{{ contact.name }}</strong>
                  <small>{{ contact.email }} - {{ contact.poste || contact.structure || 'Contact' }}</small>
                </span>
              </button>
            </div>
          </div>

          <label>
            <span>Objet</span>
            <input v-model.trim="composeForm.subject" type="text" required maxlength="180">
          </label>
          <label class="mailbox-compose-body">
            <span>Message</span>
            <textarea v-model="composeForm.body" rows="12" required></textarea>
          </label>

          <div class="mailbox-attachments">
            <input
              ref="attachmentInput"
              class="mailbox-file-input"
              type="file"
              multiple
              @change="handleAttachmentChange"
            >
            <button type="button" class="mailbox-attach-btn" @click="openAttachmentPicker">
              <i class="fas fa-paperclip"></i>
              Pieces jointes
            </button>
            <span>5 fichiers max, 10 Mo par fichier</span>
          </div>

          <div v-if="composeForm.attachments.length" class="mailbox-attachment-list">
            <div
              v-for="(file, index) in composeForm.attachments"
              :key="`${file.name}-${file.size}-${index}`"
              class="mailbox-attachment-item"
            >
              <i class="fas fa-file"></i>
              <span>{{ file.name }}</span>
              <small>{{ formatSize(file.size) }}</small>
              <button type="button" title="Retirer" @click="removeAttachment(index)">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <div v-if="composeError" class="mailbox-alert mailbox-alert-danger">
            <i class="fas fa-triangle-exclamation"></i>
            {{ composeError }}
          </div>

          <div class="mailbox-compose-actions">
            <button type="button" class="mailbox-ghost-light-btn" @click="composerOpen = false">Annuler</button>
            <button type="submit" class="mailbox-primary-btn" :disabled="sendingMail">
              <span v-if="sendingMail" class="spinner-border spinner-border-sm"></span>
              <i v-else class="fas fa-paper-plane"></i>
              Envoyer
            </button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import client from '@/api/client'

const settings = ref(null)
const loadingSettings = ref(true)
const settingsOpen = ref(false)
const savingSettings = ref(false)
const settingsError = ref('')

const folders = ref([])
const activeFolder = ref('INBOX')
const activeFilter = ref('')
const targetFolder = ref('')

const composerOpen = ref(false)
const sendingMail = ref(false)
const composeError = ref('')
const loadingContacts = ref(false)
const addressBookGroups = ref([])
const recentRecipients = ref([])
const activeRecipientField = ref('to')
const mobileMenuOpen = ref(false)
const mobileSearchOpen = ref(false)
const toInput = ref(null)
const ccInput = ref(null)
const bccInput = ref(null)
const mobileSearchInput = ref(null)
const attachmentInput = ref(null)

const messages = ref([])
const meta = ref({ total: 0, current_page: 1, last_page: 1, per_page: 15, unread_count: 0 })
const loadingMessages = ref(false)
const messagesError = ref('')
const search = ref('')
const searchScope = ref('all')
const selectedUid = ref(null)
const selectedMessage = ref(null)
const selectedBulkUids = ref([])
const selectedLoading = ref(false)
const bulkProcessing = ref(false)
const isMobileMailbox = ref(false)
const recipientDetailsOpen = ref(false)
const sidebarFoldersExpanded = ref(false)
let searchTimer = null
let mobileMediaQuery = null

const settingsForm = reactive({
  email: '',
  username: '',
  password: '',
  imap_host: 'camulus.o2switch.net',
  imap_port: 993,
  imap_encryption: 'ssl',
  signature: '',
})

const composeForm = reactive({
  to: [],
  cc: [],
  bcc: [],
  subject: '',
  body: '',
  attachments: [],
})

const recipientDraft = reactive({
  to: '',
  cc: '',
  bcc: '',
})

const showSettingsPanel = computed(() => !settings.value?.has_credentials || settingsOpen.value)
const accountInitial = computed(() => (settings.value?.account_email?.charAt(0) || '@').toUpperCase())
const inboxFolder = computed(() => folders.value.find(folder => folder.type === 'inbox') || folders.value[0])
const archiveFolder = computed(() => folders.value.find(folder => folder.type === 'archive'))
const trashFolder = computed(() => folders.value.find(folder => folder.type === 'trash'))
const activeFolderMeta = computed(() => folders.value.find(folder => folder.name === activeFolder.value))
const moveTargets = computed(() => folders.value.filter(folder => folder.name !== activeFolder.value))
const essentialFolderTypes = ['sent', 'drafts', 'archive', 'junk', 'trash']
const essentialFolders = computed(() => essentialFolderTypes
  .map(type => folders.value.find(folder => folder.type === type))
  .filter(Boolean))
const secondaryFolders = computed(() => folders.value.filter(folder => !['inbox', ...essentialFolderTypes].includes(folder.type)))
const visibleSecondaryFolders = computed(() => (
  sidebarFoldersExpanded.value ? secondaryFolders.value : secondaryFolders.value.slice(0, 5)
))
const isInboxActive = computed(() => activeFilter.value === '' && activeFolder.value === inboxFolder.value?.name)
const activeTitle = computed(() => {
  if (activeFilter.value === 'unread') return 'Messages non lus'
  if (activeFilter.value === 'flagged') return 'Messages avec etoile'
  return activeFolderMeta.value?.label || 'Boite mail'
})
const activeSubtitle = computed(() => activeFilter.value ? 'Vue rapide' : 'Dossier')
const selectedBulkSet = computed(() => new Set(selectedBulkUids.value))
const selectedBulkMessages = computed(() => messages.value.filter(message => selectedBulkSet.value.has(message.uid)))
const selectedBulkCount = computed(() => selectedBulkUids.value.length)
const pageSelectableMessages = computed(() => messages.value)
const allPageSelected = computed(() => pageSelectableMessages.value.length > 0
  && pageSelectableMessages.value.every(message => selectedBulkSet.value.has(message.uid)))
const somePageSelected = computed(() => selectedBulkCount.value > 0 && !allPageSelected.value)
const actionMessages = computed(() => {
  if (selectedBulkMessages.value.length) return selectedBulkMessages.value
  return selectedMessage.value ? [selectedMessage.value] : []
})
const hasActionTarget = computed(() => actionMessages.value.length > 0)
const readActionLabel = computed(() => actionMessages.value.some(message => message.unread) ? 'Marquer lu' : 'Non lu')
const flagActionLabel = computed(() => actionMessages.value.some(message => !message.flagged) ? 'Etoile' : 'Retirer etoile')
const readerBodyBlocks = computed(() => formatMailBody(selectedMessage.value?.body || ''))
const signaturePreview = computed(() => parseSignaturePreview(settingsForm.signature))
const contactOptions = computed(() => {
  const contacts = []
  const seen = new Set()

  for (const group of addressBookGroups.value) {
    for (const agent of group.agents || []) {
      const email = normalizeEmail(agent.email_professionnel || agent.emails?.[0])
      if (!email || seen.has(email)) continue

      seen.add(email)
      contacts.push({
        id: agent.id,
        name: agent.nom_complet || email,
        email,
        poste: agent.poste || group.poste || '',
        structure: agent.structure || '',
        source: 'agent',
        recent: false,
        search: normalizeText([
          agent.nom_complet,
          email,
          agent.poste,
          group.poste,
          agent.structure,
        ].filter(Boolean).join(' ')),
      })
    }
  }

  for (const recipient of recentRecipients.value) {
    const email = normalizeEmail(recipient.email)
    if (!email || seen.has(email)) continue

    seen.add(email)
    contacts.push({
      id: `recent-${email}`,
      name: recipient.name || email,
      email,
      poste: recipient.label || 'Destinataire recent',
      structure: 'Mail recent',
      source: 'recent',
      recent: true,
      search: normalizeText([
        recipient.name,
        email,
        recipient.label,
        'recent',
      ].filter(Boolean).join(' ')),
    })
  }

  return contacts
})
const smartGroups = computed(() => {
  const contacts = contactOptions.value
  const by = (key, label, icon, predicate) => ({
    key,
    label,
    icon,
    contacts: contacts.filter(predicate),
  })

  const priorityGroups = [
    by('directeurs', 'Directeurs', 'fa-user-tie', contact => contact.search.includes('directeur') || contact.search.includes('directrice')),
    by('sen', 'SEN', 'fa-building-columns', contact => contact.search.includes('sen') || contact.search.includes('national')),
    by('sep', 'SEP', 'fa-location-dot', contact => contact.search.includes('sep') || contact.search.includes('provincial')),
    by('rh', 'Ressources humaines', 'fa-people-group', contact => contact.search.includes('ressource humaine') || contact.search.includes('rh')),
    by('chefs', 'Chefs de section', 'fa-sitemap', contact => contact.search.includes('chef') && contact.search.includes('section')),
    by('assistants', 'Assistants', 'fa-user-check', contact => contact.search.includes('assistant')),
  ]

  const posteGroups = addressBookGroups.value
    .map(group => ({
      key: `poste-${normalizeText(group.poste)}`,
      label: group.poste,
      icon: 'fa-id-badge',
      contacts: contacts.filter(contact => contact.source === 'agent' && normalizeText(contact.poste) === normalizeText(group.poste)),
    }))
    .filter(group => group.contacts.length > 1)

  const seen = new Set()

  return [...priorityGroups, ...posteGroups]
    .filter(group => group.contacts.length)
    .filter(group => {
      const key = normalizeText(group.label)
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
    .slice(0, 18)
})
const filteredContactSuggestions = computed(() => {
  const field = activeRecipientField.value
  const term = normalizeText(recipientDraft[field] || '')
  const pool = contactOptions.value.filter(contact => !isRecipientSelected(field, contact.email))
  const bySmartOrder = (first, second) => Number(second.recent) - Number(first.recent)
    || (first.name || first.email).localeCompare(second.name || second.email)

  if (!term) {
    return pool
      .sort(bySmartOrder)
      .slice(0, 8)
  }

  return pool
    .filter(contact => contact.search.includes(term))
    .sort(bySmartOrder)
    .slice(0, 10)
})

function hydrateSettingsForm(payload) {
  settingsForm.email = payload?.account_email || ''
  settingsForm.username = payload?.username || payload?.account_email || ''
  settingsForm.password = ''
  settingsForm.imap_host = payload?.imap?.host || 'camulus.o2switch.net'
  settingsForm.imap_port = payload?.imap?.port || 993
  settingsForm.imap_encryption = payload?.imap?.encryption || 'ssl'
  settingsForm.signature = payload?.signature || payload?.default_signature || ''
}

async function loadSettings() {
  loadingSettings.value = true
  settingsError.value = ''
  try {
    const { data } = await client.get('/mailbox/settings')
    settings.value = data.data
    hydrateSettingsForm(data.data)
    settingsOpen.value = !data.data?.has_credentials
    if (data.data?.has_credentials) {
      await loadFolders()
      await loadContacts()
      activeFolder.value = inboxFolder.value?.name || 'INBOX'
      await loadMessages(1)
    }
  } catch (error) {
    settingsError.value = firstError(error) || 'Impossible de charger les reglages mail.'
  } finally {
    loadingSettings.value = false
  }
}

async function saveMailboxSettings() {
  savingSettings.value = true
  settingsError.value = ''

  try {
    const payload = {
      email: settingsForm.email,
      username: settingsForm.username,
      imap_host: settingsForm.imap_host,
      imap_port: settingsForm.imap_port,
      imap_encryption: settingsForm.imap_encryption,
      signature: normalizeSignatureText(settingsForm.signature),
    }

    if (settingsForm.password) {
      payload.password = settingsForm.password
    }

    const { data } = await client.post('/mailbox/settings', payload)
    settings.value = data.data
    hydrateSettingsForm(data.data)
    settingsOpen.value = false
    await loadFolders()
    await loadContacts()
    activeFolder.value = inboxFolder.value?.name || 'INBOX'
    await loadMessages(1)
  } catch (error) {
    settingsError.value = firstError(error) || 'Connexion a la boite mail impossible.'
  } finally {
    savingSettings.value = false
  }
}

function resetSignatureFromDefault() {
  settingsForm.signature = settings.value?.default_signature || ''
}

function normalizeSignatureText(value) {
  return String(value || '')
    .replace(/\r\n?/g, '\n')
    .replace(/^-- ?\n/, '')
    .replace(/\n{3,}/g, '\n\n')
    .trim()
}

function parseSignaturePreview(value) {
  const lines = normalizeSignatureText(value)
    .split('\n')
    .map(line => line.trim())
    .filter(Boolean)

  let greeting = ''

  if (/^(cordialement|bien a vous)/i.test(lines[0] || '')) {
    greeting = lines.shift()
  }

  const name = lines.shift() || settings.value?.sender_name || 'E-PNMLS'
  const role = lines.length && !isSignatureContactLine(lines[0]) ? lines.shift() : ''

  return {
    greeting,
    name,
    role,
    details: lines,
  }
}

function isSignatureContactLine(line) {
  const value = normalizeText(line)

  return value.includes('@')
    || value.includes('email')
    || value.includes('mail')
    || value.includes('tel')
    || value.includes('phone')
}

function composeSignatureBlock() {
  const signature = normalizeSignatureText(settings.value?.signature || settingsForm.signature)

  return signature ? `\n\n-- \n${signature}` : ''
}

async function loadFolders() {
  try {
    const { data } = await client.get('/mailbox/folders')
    folders.value = data.data || []
  } catch (_) {
    folders.value = [{ name: 'INBOX', label: 'Boite de reception', type: 'inbox', total: 0, unread: 0 }]
  }
}

async function loadContacts() {
  loadingContacts.value = true

  try {
    const { data } = await client.get('/address-book')
    addressBookGroups.value = data.data?.groups || []
    recentRecipients.value = data.data?.recent_recipients || []
  } catch (_) {
    addressBookGroups.value = []
    recentRecipients.value = []
  } finally {
    loadingContacts.value = false
  }
}

function openInbox() {
  activeFolder.value = inboxFolder.value?.name || 'INBOX'
  activeFilter.value = ''
  closeMobileMenu()
  resetSelection()
  clearBulkSelection()
  loadMessages(1)
}

function openFolder(folder) {
  activeFolder.value = folder.name
  activeFilter.value = ''
  closeMobileMenu()
  resetSelection()
  clearBulkSelection()
  loadMessages(1)
}

function openFilter(filter) {
  activeFolder.value = inboxFolder.value?.name || activeFolder.value || 'INBOX'
  activeFilter.value = filter
  closeMobileMenu()
  resetSelection()
  clearBulkSelection()
  loadMessages(1)
}

async function loadMessages(page = 1) {
  if (!settings.value?.has_credentials) return

  loadingMessages.value = true
  messagesError.value = ''

  try {
    const { data } = await client.get('/mailbox/messages', {
      params: {
        page,
        per_page: meta.value.per_page || 15,
        folder: activeFolder.value,
        filter: activeFilter.value || undefined,
        search: search.value || undefined,
        search_scope: search.value ? searchScope.value : undefined,
      },
    })

    messages.value = data.data || []
    meta.value = { ...meta.value, ...(data.meta || {}) }
    syncBulkSelection()

    if (messages.value.length && !isMobileMailbox.value) {
      const currentStillVisible = messages.value.some(message => message.uid === selectedUid.value)
      if (!currentStillVisible) {
        await selectMessage(messages.value[0])
      }
    } else if (messages.value.length) {
      const currentStillVisible = messages.value.some(message => message.uid === selectedUid.value)
      if (!currentStillVisible) {
        resetSelection()
      }
    } else {
      resetSelection()
    }
  } catch (error) {
    messages.value = []
    resetSelection()
    messagesError.value = firstError(error) || 'Impossible de lire la boite mail.'
    if (error.response?.status === 422) {
      settingsOpen.value = true
    }
  } finally {
    loadingMessages.value = false
  }
}

async function selectMessage(message) {
  selectedUid.value = message.uid
  selectedLoading.value = true
  recipientDetailsOpen.value = false

  try {
    const { data } = await client.get(`/mailbox/messages/${message.uid}`, {
      params: { folder: activeFolder.value },
    })
    selectedMessage.value = data.data
    patchMessage(message.uid, { seen: true, unread: false })
    if (message.unread) {
      meta.value.unread_count = Math.max((meta.value.unread_count || 0) - 1, 0)
      adjustFolderUnread(activeFolder.value, -1)
    }
  } catch (error) {
    messagesError.value = firstError(error) || 'Impossible d ouvrir ce mail.'
    if (isMobileMailbox.value) {
      resetSelection()
    }
  } finally {
    selectedLoading.value = false
  }
}

async function setReadState(message, seen) {
  if (!message) return
  const wasUnread = !!message.unread
  try {
    const { data } = await client.post(`/mailbox/messages/${message.uid}/read`, {
      seen,
      source_folder: activeFolder.value,
    })
    patchMessage(message.uid, data.data)
    if (selectedMessage.value?.uid === message.uid) {
      selectedMessage.value = { ...selectedMessage.value, ...data.data }
    }
    const unreadDelta = seen && wasUnread ? -1 : (!seen && !wasUnread ? 1 : 0)
    if (unreadDelta) {
      meta.value.unread_count = Math.max((meta.value.unread_count || 0) + unreadDelta, 0)
      adjustFolderUnread(activeFolder.value, unreadDelta)
    }
  } catch (error) {
    messagesError.value = firstError(error) || 'Action impossible.'
  }
}

function toggleRead(message) {
  return setReadState(message, !!message?.unread)
}

async function toggleReadAction() {
  const targets = actionMessages.value
  if (!targets.length || bulkProcessing.value) return

  const shouldMarkSeen = targets.some(message => message.unread)
  bulkProcessing.value = true
  messagesError.value = ''

  try {
    for (const message of targets) {
      await setReadState(message, shouldMarkSeen)
    }
  } finally {
    bulkProcessing.value = false
  }
}

async function setFlagState(message, flagged) {
  if (!message) return
  try {
    const { data } = await client.post(`/mailbox/messages/${message.uid}/flag`, {
      flagged,
      source_folder: activeFolder.value,
    })
    patchMessage(message.uid, data.data)
    if (selectedMessage.value?.uid === message.uid) {
      selectedMessage.value = { ...selectedMessage.value, ...data.data }
    }
  } catch (error) {
    messagesError.value = firstError(error) || 'Action impossible.'
  }
}

function toggleFlag(message) {
  return setFlagState(message, !message?.flagged)
}

async function toggleFlagAction() {
  const targets = actionMessages.value
  if (!targets.length || bulkProcessing.value) return

  const shouldFlag = targets.some(message => !message.flagged)
  bulkProcessing.value = true
  messagesError.value = ''

  try {
    for (const message of targets) {
      await setFlagState(message, shouldFlag)
    }
  } finally {
    bulkProcessing.value = false
  }
}

async function moveSelectedTo(folderName) {
  return moveMessagesTo(folderName, actionMessages.value)
}

async function moveMessagesTo(folderName, targets) {
  const selectedTargets = targets.filter(Boolean)
  if (!selectedTargets.length || !folderName || bulkProcessing.value) return

  bulkProcessing.value = true
  messagesError.value = ''
  try {
    for (const message of selectedTargets) {
      await client.post(`/mailbox/messages/${message.uid}/move`, {
        folder: folderName,
        source_folder: activeFolder.value,
      })
    }
    targetFolder.value = ''
    clearBulkSelection()
    await loadFolders()
    await loadMessages(messages.value.length > selectedTargets.length ? meta.value.current_page : Math.max(meta.value.current_page - 1, 1))
  } catch (error) {
    messagesError.value = firstError(error) || 'Deplacement impossible.'
  } finally {
    bulkProcessing.value = false
  }
}

function archiveSelected() {
  if (archiveFolder.value) {
    moveSelectedTo(archiveFolder.value.name)
  }
}

function archiveMessage(message) {
  if (archiveFolder.value && message) {
    moveMessagesTo(archiveFolder.value.name, [message])
  }
}

async function deleteMessage(uid) {
  const message = messageByUid(uid)
  if (message) {
    await deleteMessages([message])
  }
}

async function deleteActionTargets() {
  await deleteMessages(actionMessages.value)
}

async function deleteMessages(targets) {
  const selectedTargets = targets.filter(Boolean)
  if (!selectedTargets.length || bulkProcessing.value) return

  const label = selectedTargets.length > 1 ? `${selectedTargets.length} mails` : 'ce mail'
  if (!window.confirm(`Supprimer ${label} ?`)) return

  if (trashFolder.value && activeFolder.value !== trashFolder.value.name) {
    await moveMessagesTo(trashFolder.value.name, selectedTargets)
    return
  }

  bulkProcessing.value = true
  messagesError.value = ''
  try {
    for (const message of selectedTargets) {
      await client.delete(`/mailbox/messages/${message.uid}`, {
        params: { folder: activeFolder.value },
      })
    }
    clearBulkSelection()
    await loadFolders()
    await loadMessages(messages.value.length > selectedTargets.length ? meta.value.current_page : Math.max(meta.value.current_page - 1, 1))
  } catch (error) {
    messagesError.value = firstError(error) || 'Suppression impossible.'
  } finally {
    bulkProcessing.value = false
  }
}

async function sendMail() {
  commitRecipient('to', false)
  commitRecipient('cc', false)
  commitRecipient('bcc', false)

  const recipients = recipientEmails('to')

  if (!recipients.length) {
    composeError.value = 'Ajoutez au moins un destinataire.'
    return
  }

  sendingMail.value = true
  composeError.value = ''

  try {
    const payload = new FormData()
    recipients.forEach(email => payload.append('to[]', email))
    recipientEmails('cc').forEach(email => payload.append('cc[]', email))
    recipientEmails('bcc').forEach(email => payload.append('bcc[]', email))
    payload.append('subject', composeForm.subject)
    payload.append('body', composeForm.body)
    composeForm.attachments.forEach(file => payload.append('attachments[]', file))

    await client.post('/mailbox/send', payload)

    resetComposeForm()
    composerOpen.value = false
    await loadFolders()
    await loadContacts()
  } catch (error) {
    composeError.value = firstError(error) || 'Envoi impossible.'
  } finally {
    sendingMail.value = false
  }
}

function openComposer() {
  closeMobileMenu()
  composeError.value = ''
  if (!composerOpen.value && !composeForm.body) {
    composeForm.body = composeSignatureBlock()
  }
  composerOpen.value = true
  window.setTimeout(() => focusRecipient('to'), 80)
}

function resetComposeForm() {
  composeForm.to = []
  composeForm.cc = []
  composeForm.bcc = []
  composeForm.subject = ''
  composeForm.body = composeSignatureBlock()
  composeForm.attachments = []

  if (attachmentInput.value) {
    attachmentInput.value.value = ''
  }
}

function replyToSelected() {
  if (!selectedMessage.value) return
  resetComposeForm()
  const replyTarget = selectedMessage.value.reply_to_addresses?.[0]
    || selectedMessage.value.from_addresses?.[0]
    || {
      email: extractEmail(selectedMessage.value.from),
      name: cleanSender(selectedMessage.value.from),
    }
  addRecipient('to', {
    email: replyTarget.email,
    name: replyTarget.name || cleanSender(replyTarget.label || replyTarget.email),
  })
  composeForm.subject = selectedMessage.value.subject?.startsWith('Re:')
    ? selectedMessage.value.subject
    : `Re: ${selectedMessage.value.subject || ''}`.trim()
  composeForm.body = `${composeSignatureBlock()}\n\n--- Message original ---\n${selectedMessage.value.body || ''}`
  openComposer()
}

function forwardSelected() {
  if (!selectedMessage.value) return
  resetComposeForm()
  composeForm.subject = selectedMessage.value.subject?.startsWith('Tr:')
    ? selectedMessage.value.subject
    : `Tr: ${selectedMessage.value.subject || ''}`.trim()
  composeForm.body = `${composeSignatureBlock()}\n\n--- Message transfere ---\nDe: ${selectedMessage.value.from}\nDate: ${formatDate(selectedMessage.value.date)}\n\n${selectedMessage.value.body || ''}`
  openComposer()
}

function focusRecipient(field) {
  activeRecipientField.value = field
  const refs = { to: toInput, cc: ccInput, bcc: bccInput }
  refs[field]?.value?.focus()
}

function commitRecipient(field, preferSuggestion = true) {
  const draft = recipientDraft[field]
  if (!draft) return

  const tokens = draft
    .split(/[;,\n]+/)
    .map(item => item.trim())
    .filter(Boolean)

  if (!tokens.length) return

  for (const token of tokens) {
    const normalizedToken = normalizeText(token)
    const matchedGroup = smartGroups.value.find(group => normalizeText(group.label) === normalizedToken)

    if (matchedGroup) {
      addGroupRecipients(matchedGroup, field)
      continue
    }

    const suggestion = preferSuggestion
      ? filteredContactSuggestions.value.find(contact => contact.search.includes(normalizedToken))
      : null

    if (suggestion && !isEmail(token)) {
      addRecipient(field, suggestion)
      continue
    }

    if (isEmail(token)) {
      addRecipient(field, { email: token, name: token })
    } else if (preferSuggestion) {
      composeError.value = 'Selectionnez un contact suggere ou saisissez une adresse email valide.'
    }
  }

  recipientDraft[field] = ''
}

function addGroupRecipients(group, field = activeRecipientField.value || 'to') {
  for (const contact of group.contacts) {
    addRecipient(field, contact)
  }
  recipientDraft[field] = ''
  activeRecipientField.value = field
}

function addRecipient(field, contact) {
  const email = normalizeEmail(contact.email)
  if (!email || !isEmail(email)) return

  if (isRecipientSelected(field, email)) {
    recipientDraft[field] = ''
    return
  }

  composeForm[field].push({
    email,
    name: contact.name && contact.name !== email ? contact.name : '',
  })
  recipientDraft[field] = ''
  composeError.value = ''
}

function removeRecipient(field, email) {
  composeForm[field] = composeForm[field].filter(recipient => recipient.email !== email)
}

function removeLastRecipient(field, event) {
  if (recipientDraft[field]) return
  if (event.key !== 'Backspace') return

  composeForm[field].pop()
}

function isRecipientSelected(field, email) {
  const normalizedEmail = normalizeEmail(email)
  return composeForm[field].some(recipient => recipient.email === normalizedEmail)
}

function recipientEmails(field) {
  return composeForm[field].map(recipient => recipient.email)
}

function openAttachmentPicker() {
  attachmentInput.value?.click()
}

function handleAttachmentChange(event) {
  const files = Array.from(event.target.files || [])
  const nextAttachments = [...composeForm.attachments]
  composeError.value = ''

  for (const file of files) {
    if (nextAttachments.length >= 5) {
      composeError.value = 'Maximum 5 pieces jointes par mail.'
      break
    }

    if (file.size > 10 * 1024 * 1024) {
      composeError.value = `${file.name} depasse 10 Mo.`
      continue
    }

    const alreadyAdded = nextAttachments.some(item => (
      item.name === file.name
      && item.size === file.size
      && item.lastModified === file.lastModified
    ))

    if (!alreadyAdded) {
      nextAttachments.push(file)
    }
  }

  composeForm.attachments = nextAttachments
  event.target.value = ''
}

function removeAttachment(index) {
  composeForm.attachments = composeForm.attachments.filter((_, itemIndex) => itemIndex !== index)
}

async function downloadAttachment(attachment) {
  if (!selectedMessage.value?.uid || !attachment?.part) return

  try {
    const response = await client.get(`/mailbox/messages/${selectedMessage.value.uid}/attachments/${encodeURIComponent(attachment.part)}`, {
      params: { folder: activeFolder.value },
      responseType: 'blob',
    })
    const filename = filenameFromDisposition(response.headers?.['content-disposition'])
      || attachment.name
      || 'piece-jointe'
    const blob = new Blob([response.data], { type: attachment.mime || response.data?.type || 'application/octet-stream' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.setTimeout(() => URL.revokeObjectURL(url), 250)
  } catch (error) {
    messagesError.value = firstError(error) || 'Telechargement de la piece jointe impossible.'
  }
}

function contactInitial(contact) {
  return (contact.name?.charAt(0) || contact.email?.charAt(0) || '@').toUpperCase()
}

function resetSelection() {
  selectedUid.value = null
  selectedMessage.value = null
  targetFolder.value = ''
  recipientDetailsOpen.value = false
}

function messageByUid(uid) {
  return messages.value.find(message => message.uid === uid)
    || (selectedMessage.value?.uid === uid ? selectedMessage.value : null)
}

function isBulkSelected(uid) {
  return selectedBulkSet.value.has(uid)
}

function toggleMessageSelection(message) {
  if (!message?.uid) return

  selectedBulkUids.value = isBulkSelected(message.uid)
    ? selectedBulkUids.value.filter(uid => uid !== message.uid)
    : [...selectedBulkUids.value, message.uid]
}

function togglePageSelection() {
  if (!pageSelectableMessages.value.length) return

  selectedBulkUids.value = allPageSelected.value
    ? []
    : pageSelectableMessages.value.map(message => message.uid)
}

function clearBulkSelection() {
  selectedBulkUids.value = []
}

function syncBulkSelection() {
  const visibleUids = new Set(messages.value.map(message => message.uid))
  selectedBulkUids.value = selectedBulkUids.value.filter(uid => visibleUids.has(uid))
}

function openMobileMenu() {
  mobileMenuOpen.value = true
}

function closeMobileMenu() {
  mobileMenuOpen.value = false
}

function toggleMobileSearch() {
  mobileSearchOpen.value = !mobileSearchOpen.value

  if (mobileSearchOpen.value) {
    window.setTimeout(() => mobileSearchInput.value?.focus(), 80)
  }
}

function patchMessage(uid, patch) {
  messages.value = messages.value.map(message => (
    message.uid === uid ? { ...message, ...patch } : message
  ))
}

function adjustFolderUnread(folderName, delta) {
  if (!folderName || !delta) return

  folders.value = folders.value.map(folder => {
    if (folder.name !== folderName) return folder

    return {
      ...folder,
      unread: Math.max(Number(folder.unread || 0) + delta, 0),
    }
  })
}

function onSearch() {
  window.clearTimeout(searchTimer)
  resetSelection()
  clearBulkSelection()
  searchTimer = window.setTimeout(() => loadMessages(1), 400)
}

function submitSearch() {
  window.clearTimeout(searchTimer)
  resetSelection()
  clearBulkSelection()
  loadMessages(1)
}

function clearSearch() {
  if (!search.value) return
  search.value = ''
  submitSearch()
}

function firstError(error) {
  const errors = error.response?.data?.errors
  if (errors) {
    const first = Object.values(errors).flat()[0]
    if (first) return first
  }

  return error.response?.data?.message || error.message
}

function cleanSender(value) {
  if (!value) return ''
  return value.replace(/<[^>]+>/g, '').replace(/"/g, '').trim()
}

function normalizeEmail(value) {
  return (value || '').toString().trim().toLowerCase()
}

function isEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizeEmail(value))
}

function normalizeText(value) {
  return (value || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

function formatMailBody(value) {
  const lines = normalizeMailText(value)
    .split('\n')
    .map(line => line.trimEnd())

  const meaningfulLines = lines.filter(line => line.trim() !== '')
  if (!meaningfulLines.length) return []

  const { contentLines, signatureLines } = splitMailSignature(lines)
  const blocks = buildMailBodyBlocks(contentLines)

  if (signatureLines.length) {
    blocks.push({
      type: 'signature',
      lines: signatureLines.map(line => renderMailLine(stripQuoteMarks(line))).filter(Boolean),
    })
  }

  return blocks
}

function normalizeMailText(value) {
  return (value || '')
    .toString()
    .replace(/\r\n?/g, '\n')
    .replace(/\u00a0/g, ' ')
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{4,}/g, '\n\n\n')
    .trim()
}

function splitMailSignature(lines) {
  const delimiterIndex = lines.findIndex(line => /^--\s*$/.test(line.trim()))
  if (delimiterIndex >= 0) {
    return {
      contentLines: lines.slice(0, delimiterIndex),
      signatureLines: trimMailLines(lines.slice(delimiterIndex + 1)),
    }
  }

  const signatureStart = findSignatureStart(lines)
  if (signatureStart >= 0) {
    return {
      contentLines: lines.slice(0, signatureStart),
      signatureLines: trimMailLines(lines.slice(signatureStart)),
    }
  }

  return { contentLines: lines, signatureLines: [] }
}

function findSignatureStart(lines) {
  const start = Math.max(0, lines.length - 14)

  for (let index = lines.length - 1; index >= start; index -= 1) {
    const line = normalizeText(lines[index]).replace(/[,.!;:\s]+$/g, '')
    if (!line) continue

    if (/^(cordialement|bien cordialement|sincerement|respectueusement|salutations|bien a vous|best regards|kind regards|regards|thanks|merci)$/.test(line)) {
      return index
    }

    if (/^sent from my /.test(line)) {
      return index
    }
  }

  return -1
}

function trimMailLines(lines) {
  const copy = [...lines]
  while (copy.length && copy[0].trim() === '') copy.shift()
  while (copy.length && copy[copy.length - 1].trim() === '') copy.pop()
  return copy
}

function buildMailBodyBlocks(lines) {
  const blocks = []
  let buffer = []
  let currentType = 'paragraph'

  const flush = () => {
    const cleaned = trimMailLines(buffer).map(line => stripQuoteMarks(line)).filter(line => line.trim() !== '')
    if (!cleaned.length) {
      buffer = []
      return
    }

    const renderedLines = currentType === 'paragraph' && !looksLikeList(cleaned)
      ? [renderMailLine(joinMailParagraph(cleaned))]
      : cleaned.map(line => renderMailLine(line))

    blocks.push({ type: currentType, lines: renderedLines.filter(Boolean) })
    buffer = []
  }

  for (const line of lines) {
    if (line.trim() === '') {
      flush()
      currentType = 'paragraph'
      continue
    }

    const type = classifyMailLine(line)
    if (buffer.length && type !== currentType) {
      flush()
    }

    currentType = type
    buffer.push(line)
  }

  flush()

  return blocks
}

function classifyMailLine(line) {
  const trimmed = line.trim()

  if (/^>/.test(trimmed)) return 'quote'
  if (/^[-_]{2,}\s*(message original|original message|forwarded message|message transfere|message transferé|message transféré)\s*[-_]{2,}$/i.test(trimmed)) return 'quote'
  if (/^(le .+ a écrit\s*:|on .+ wrote\s*:)/i.test(trimmed)) return 'quote'
  if (/^(de|from|envoye|envoyé|sent|a|to|cc|objet|subject)\s*:/i.test(trimmed)) return 'quote'

  return 'paragraph'
}

function stripQuoteMarks(line) {
  return line.replace(/^\s*>+\s?/, '')
}

function looksLikeList(lines) {
  return lines.some(line => /^\s*(?:[-*]|\d+[.)])\s+/.test(line))
}

function joinMailParagraph(lines) {
  return lines
    .map(line => line.trim())
    .join(' ')
    .replace(/\s{2,}/g, ' ')
}

function renderMailLine(value) {
  const text = value.trim()
  if (!text) return ''

  const pattern = /((?:https?:\/\/|www\.)[^\s<]+|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/gi
  let output = ''
  let lastIndex = 0

  text.replace(pattern, (match, _token, offset) => {
    output += escapeHtml(text.slice(lastIndex, offset))

    const { token, trailing } = splitTrailingPunctuation(match)
    const href = safeMailHref(token)

    if (href) {
      output += `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(token)}</a>`
    } else {
      output += escapeHtml(token)
    }

    output += escapeHtml(trailing)
    lastIndex = offset + match.length
    return match
  })

  output += escapeHtml(text.slice(lastIndex))

  return output
}

function splitTrailingPunctuation(value) {
  let token = value
  let trailing = ''

  while (/[),.;:!?]$/.test(token)) {
    trailing = token.slice(-1) + trailing
    token = token.slice(0, -1)
  }

  return { token, trailing }
}

function safeMailHref(value) {
  const token = value.trim()
  if (!token) return ''

  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(token)) {
    return `mailto:${token}`
  }

  const url = token.startsWith('www.') ? `https://${token}` : token

  try {
    const parsed = new URL(url)
    return ['http:', 'https:'].includes(parsed.protocol) ? parsed.href : ''
  } catch (_) {
    return ''
  }
}

function escapeHtml(value) {
  return (value || '').toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function extractEmail(value) {
  if (!value) return ''
  const match = value.match(/<([^>]+)>/)
  return (match?.[1] || value).replace(/"/g, '').trim()
}

function addressLine(addresses, fallback = '') {
  return addressItems(addresses, fallback).join(', ')
}

function addressItems(addresses, fallback = '') {
  if (Array.isArray(addresses) && addresses.length) {
    return addresses
      .map(address => address.label || address.email || '')
      .filter(Boolean)
  }

  return fallback
    ? fallback.split(/,\s*/).map(item => item.trim()).filter(Boolean)
    : []
}

function compactAddressLine(addresses, fallback = '', limit = 1) {
  const items = addressItems(addresses, fallback)
  if (items.length <= limit) return items.join(', ')
  return `${items.slice(0, limit).join(', ')} +${items.length - limit}`
}

function hasRecipientDetails(message) {
  if (!message) return false

  return addressItems(message.to_addresses, message.to).length > 1
    || addressItems(message.cc_addresses, message.cc).length > 0
    || addressItems(message.bcc_addresses, message.bcc).length > 0
}

function senderInitial(value) {
  const cleaned = cleanSender(value)
  return (cleaned.charAt(0) || '@').toUpperCase()
}

function attachmentIcon(mime = '') {
  if (mime.startsWith('image/')) return 'fa-file-image'
  if (mime.includes('pdf')) return 'fa-file-pdf'
  if (mime.includes('word') || mime.includes('document')) return 'fa-file-word'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return 'fa-file-excel'
  if (mime.startsWith('text/')) return 'fa-file-lines'
  return 'fa-file'
}

function filenameFromDisposition(value = '') {
  const encoded = value.match(/filename\*=UTF-8''([^;]+)/i)
  if (encoded?.[1]) {
    try {
      return decodeURIComponent(encoded[1])
    } catch (_) {
      return encoded[1]
    }
  }

  return value.match(/filename="?([^";]+)"?/i)?.[1] || ''
}

function folderIcon(type) {
  return {
    inbox: 'fa-inbox',
    sent: 'fa-paper-plane',
    drafts: 'fa-file-lines',
    archive: 'fa-box-archive',
    junk: 'fa-ban',
    trash: 'fa-trash',
    folder: 'fa-folder',
  }[type] || 'fa-folder'
}

function formatDate(value, compact = false) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleDateString('fr-FR', compact
    ? { day: '2-digit', month: '2-digit' }
    : { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatSize(size) {
  const value = Number(size || 0)
  if (value < 1024) return `${value} o`
  if (value < 1024 * 1024) return `${Math.round(value / 1024)} Ko`
  return `${(value / 1024 / 1024).toFixed(1)} Mo`
}

function syncMobileMailbox(event = null) {
  const nextState = Boolean(event?.matches ?? mobileMediaQuery?.matches)
  isMobileMailbox.value = nextState

  if (!nextState) {
    closeMobileMenu()
    mobileSearchOpen.value = false
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    mobileMediaQuery = window.matchMedia('(max-width: 860px)')
    syncMobileMailbox()

    if (mobileMediaQuery.addEventListener) {
      mobileMediaQuery.addEventListener('change', syncMobileMailbox)
    } else {
      mobileMediaQuery.addListener(syncMobileMailbox)
    }
  }

  loadSettings()
})

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer)

  if (!mobileMediaQuery) return

  if (mobileMediaQuery.removeEventListener) {
    mobileMediaQuery.removeEventListener('change', syncMobileMailbox)
  } else {
    mobileMediaQuery.removeListener(syncMobileMailbox)
  }
})
</script>

<style scoped>
.mailbox-page {
  min-height: calc(100vh - 88px);
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
  padding: 14px;
  color: #172033;
  background: #eef6fa;
  overflow: hidden;
}

.outlook-shell,
.mailbox-settings {
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 14px 42px rgba(15, 76, 92, 0.14);
}

.outlook-shell {
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  min-height: calc(100vh - 116px);
  overflow: hidden;
}

.mailbox-sidebar {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 14px;
  background: #f4f9fb;
  border-right: 1px solid #dbeaf0;
}

.mailbox-account {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 10px;
  align-items: center;
  padding: 10px;
  border: 1px solid #d8e9ef;
  border-radius: 8px;
  background: #fff;
}

.mailbox-account-avatar,
.mailbox-avatar {
  display: grid;
  place-items: center;
  border-radius: 8px;
  font-weight: 900;
}

.mailbox-account-avatar {
  width: 44px;
  height: 44px;
  color: #fff;
  background: #05769a;
}

.mailbox-account span,
.mailbox-side-card span,
.mailbox-kicker {
  display: block;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0;
  text-transform: uppercase;
}

.mailbox-account strong {
  display: block;
  overflow: hidden;
  color: #102a43;
  font-size: 0.86rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-compose-btn,
.mailbox-primary-btn,
.mailbox-command,
.mailbox-icon-btn,
.mailbox-ghost-light-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 38px;
  border-radius: 8px;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.mailbox-compose-btn,
.mailbox-primary-btn,
.mailbox-command.primary {
  border: 0;
  color: #fff;
  background: #04769a;
}

.mailbox-compose-btn {
  width: 100%;
}

.mailbox-command,
.mailbox-ghost-light-btn {
  border: 1px solid #cfe1e8;
  color: #0f5f76;
  background: #fff;
}

.mailbox-icon-btn {
  width: 38px;
  border: 0;
  color: #0f5f76;
  background: #e7f3f7;
}

.mailbox-command:hover,
.mailbox-compose-btn:hover,
.mailbox-primary-btn:hover,
.mailbox-icon-btn:hover {
  transform: translateY(-1px);
}

.mailbox-command:disabled,
.mailbox-primary-btn:disabled,
.mailbox-icon-btn:disabled,
.mailbox-move select:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  transform: none;
}

.mailbox-command.danger {
  color: #b42318;
  border-color: #fecaca;
  background: #fff7f7;
}

.mailbox-command.settings {
  width: 38px;
  padding: 0;
  margin-left: auto;
}

.mailbox-folder-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 0;
  overflow: auto;
}

.mailbox-nav-title {
  margin: 10px 8px 4px;
  color: #6b7b8d;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.mailbox-folder-btn {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr) auto;
  gap: 8px;
  align-items: center;
  min-height: 36px;
  padding: 0 9px;
  border: 1px solid transparent;
  border-radius: 8px;
  color: #23364d;
  background: transparent;
  text-align: left;
  text-decoration: none;
}

.mailbox-folder-btn span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-folder-btn:hover,
.mailbox-folder-btn.active {
  color: #075985;
  background: #e7f5fa;
  border-color: #c3e3ee;
}

.mailbox-folder-btn b {
  min-width: 22px;
  padding: 2px 7px;
  border-radius: 999px;
  color: #fff;
  background: #0b83a5;
  font-size: 0.72rem;
  text-align: center;
}

.mailbox-folder-more {
  color: #0f5f76;
  background: #f1f8fb;
}

.mailbox-side-card {
  margin-top: auto;
  padding: 12px;
  border: 1px solid #d8e9ef;
  border-radius: 8px;
  background: #fff;
}

.mailbox-side-card strong {
  display: block;
  margin-top: 2px;
  color: #102a43;
}

.mailbox-main {
  display: flex;
  min-width: 0;
  min-height: 0;
  flex-direction: column;
  overflow: hidden;
  background: #fff;
}

.mailbox-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  min-width: 0;
  padding: 14px 16px;
  border-bottom: 1px solid #e0edf2;
  background: #fff;
}

.mailbox-topbar h1,
.mailbox-settings h1,
.mailbox-compose-head h2 {
  margin: 2px 0 0;
  color: #102a43;
  font-size: 1.22rem;
  font-weight: 900;
}

.mailbox-search {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
  max-width: 580px;
}

.mailbox-search-field {
  position: relative;
  flex: 1;
  min-width: 0;
}

.mailbox-search-field > i {
  position: absolute;
  top: 50%;
  left: 13px;
  color: #5f7f8c;
  transform: translateY(-50%);
}

.mailbox-search input,
.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-settings-form textarea,
.mailbox-compose-form input,
.mailbox-compose-form textarea,
.mailbox-move select {
  width: 100%;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
  color: #102a43;
  outline: none;
}

.mailbox-search input {
  min-height: 40px;
  padding: 0 40px 0 40px;
}

.mailbox-search-field button {
  position: absolute;
  top: 50%;
  right: 8px;
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: 999px;
  color: #64748b;
  background: transparent;
  transform: translateY(-50%);
}

.mailbox-search-field button:hover {
  color: #0f5f76;
  background: #e7f3f7;
}

.mailbox-search-scope {
  width: 132px;
  min-height: 40px;
  padding: 0 10px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-commandbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  min-width: 0;
  max-width: 100%;
  padding: 10px 12px;
  border-bottom: 1px solid #e0edf2;
  background: #f8fbfc;
}

.mailbox-command {
  min-height: 34px;
  padding: 0 11px;
  font-size: 0.84rem;
}

.mailbox-selection-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  max-width: 240px;
  padding: 0 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  color: #64748b;
  background: #fff;
  font-size: 0.82rem;
  font-weight: 800;
}

.mailbox-selection-status span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-selection-status.active {
  color: #075985;
  border-color: #93d6ea;
  background: #edfaff;
}

.mailbox-selection-status button {
  border: 0;
  color: #0f5f76;
  background: transparent;
  font-weight: 900;
}

.mailbox-move {
  display: flex;
  gap: 6px;
  align-items: center;
}

.mailbox-move select {
  min-height: 34px;
  min-width: 164px;
  padding: 0 10px;
  font-size: 0.84rem;
  font-weight: 700;
}

.mailbox-content {
  display: grid;
  grid-template-columns: minmax(300px, 390px) minmax(0, 1fr);
  min-height: 0;
  flex: 1;
  overflow: hidden;
}

.mailbox-list-pane {
  display: flex;
  min-width: 0;
  min-height: 0;
  flex-direction: column;
  overflow: hidden;
  border-right: 1px solid #e0edf2;
  background: #f8fbfc;
}

.mailbox-list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid #e0edf2;
  color: #64748b;
  font-size: 0.82rem;
}

.mailbox-list-head-main {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.mailbox-list-head strong {
  color: #102a43;
}

.mailbox-select-toggle,
.mailbox-message-check {
  display: grid;
  place-items: center;
  border: 0;
  color: #94a3b8;
  background: transparent;
}

.mailbox-select-toggle {
  width: 28px;
  height: 28px;
  border-radius: 8px;
}

.mailbox-message-check {
  align-self: center;
  width: 30px;
  height: 30px;
  border-radius: 999px;
}

.mailbox-select-toggle.active,
.mailbox-message-check.active,
.mailbox-select-toggle:hover,
.mailbox-message-check:hover {
  color: #0877b7;
  background: #e9f7fb;
}

.mailbox-message-list {
  min-height: 0;
  overflow: auto;
}

.mailbox-message-item {
  position: relative;
  display: grid;
  grid-template-columns: 30px 42px minmax(0, 1fr);
  gap: 10px;
  width: 100%;
  padding: 13px 12px;
  border: 0;
  border-bottom: 1px solid #e0edf2;
  background: transparent;
  color: #243b53;
  text-align: left;
  cursor: pointer;
}

.mailbox-message-item.unread {
  background: #f8fcff;
}

.mailbox-message-item.unread::before {
  content: '';
  position: absolute;
  left: 0;
  top: 13px;
  bottom: 13px;
  width: 3px;
  border-radius: 0 999px 999px 0;
  background: #087da0;
}

.mailbox-message-item:hover,
.mailbox-message-item.active {
  background: #e9f7fb;
}

.mailbox-message-item.selected {
  background: #edfaff;
}

.mailbox-message-item.active {
  box-shadow: inset 3px 0 0 #087da0;
}

.mailbox-message-item.unread.active {
  box-shadow: inset 3px 0 0 #075f7a;
}

.mailbox-message-item.unread .mailbox-subject,
.mailbox-message-item.unread strong {
  color: #062f4f;
  font-weight: 900;
}

.mailbox-message-item.unread .mailbox-message-top time,
.mailbox-message-item.unread .mailbox-message-meta {
  color: #12344d;
  font-weight: 800;
}

.mailbox-message-item:not(.unread) .mailbox-message-top strong {
  color: #475569;
  font-weight: 650;
}

.mailbox-message-item:not(.unread) .mailbox-subject {
  color: #64748b;
  font-weight: 500;
}

.mailbox-message-item:not(.unread) .mailbox-message-meta,
.mailbox-message-item:not(.unread) .mailbox-message-top time {
  color: #7b8794;
  font-weight: 500;
}

.mailbox-avatar {
  width: 42px;
  height: 42px;
  color: #06708f;
  background: #d9f3f6;
}

.mailbox-message-content,
.mailbox-message-top {
  min-width: 0;
}

.mailbox-message-top {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.mailbox-message-top strong,
.mailbox-subject {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-message-top strong {
  display: block;
  color: #334155;
  font-size: 0.9rem;
}

.mailbox-message-top time,
.mailbox-message-meta {
  color: #64748b;
  font-size: 0.78rem;
}

.mailbox-subject {
  display: block;
  margin-top: 3px;
  color: #475569;
  font-size: 0.88rem;
  font-weight: 600;
}

.mailbox-message-meta {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-top: 6px;
}

.mailbox-message-meta .fa-star {
  color: #c28a05;
}

.mailbox-reader {
  display: flex;
  min-width: 0;
  min-height: 0;
  flex-direction: column;
  overflow: auto;
  background: #fff;
}

.mailbox-reader-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 18px;
  border-bottom: 1px solid #e2edf2;
}

.mailbox-reader-head h2 {
  margin: 0 0 8px;
  color: #102a43;
  font-size: 1.18rem;
  font-weight: 900;
}

.mailbox-reader-main {
  min-width: 0;
  flex: 1;
}

.mailbox-reader-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
  color: #64748b;
  font-size: 0.86rem;
}

.mailbox-reader-meta-compact {
  align-items: center;
  max-width: 100%;
}

.mailbox-reader-meta-line {
  display: inline-flex;
  min-width: 0;
  max-width: 100%;
  gap: 6px;
}

.mailbox-reader-meta-line.compact {
  max-width: min(100%, 360px);
}

.mailbox-reader-meta-line b {
  flex: 0 0 auto;
  color: #0f5f76;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0;
  text-transform: uppercase;
}

.mailbox-reader-meta-line span {
  min-width: 0;
  overflow-wrap: anywhere;
}

.mailbox-reader-meta-line.compact span,
.mailbox-reader-date {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-reader-date {
  color: #64748b;
}

.mailbox-recipient-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 28px;
  padding: 0 10px;
  border: 1px solid #dbe7ef;
  border-radius: 999px;
  color: #0f5f76;
  background: #f8fafc;
  font-size: .78rem;
  font-weight: 900;
}

.mailbox-recipient-toggle:hover {
  border-color: #8fd3e8;
  background: #eef9fc;
}

.mailbox-recipient-details {
  display: grid;
  gap: 7px;
  max-height: 140px;
  margin-top: 10px;
  padding: 10px 12px;
  overflow: auto;
  border: 1px solid #dbe7ef;
  border-radius: 12px;
  background: #f8fafc;
  color: #64748b;
  font-size: .82rem;
}

.mailbox-reader-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.mailbox-reader-back {
  display: none;
}

.mailbox-reader-tools {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px 18px;
  border-bottom: 1px solid #e2edf2;
  background: #fbfdfe;
}

.mailbox-reader-tools button {
  display: inline-flex;
  gap: 7px;
  align-items: center;
  min-height: 32px;
  padding: 0 10px;
  border: 1px solid #d6e6ec;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-reader-tools button.danger {
  color: #b42318;
  border-color: #fecaca;
}

.mailbox-reader-attachments {
  display: grid;
  gap: 10px;
  padding: 12px 18px;
  border-bottom: 1px solid #e2edf2;
  background: #f8fbfc;
}

.mailbox-reader-attachments > strong {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #102a43;
  font-size: 0.88rem;
  font-weight: 900;
}

.mailbox-reader-attachment-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 8px;
}

.mailbox-reader-attachment {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr) 28px;
  align-items: center;
  gap: 9px;
  min-height: 52px;
  padding: 8px 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  color: #334155;
  background: #fff;
  text-align: left;
}

.mailbox-reader-attachment:hover {
  border-color: #8fd3e8;
  background: #eef9fc;
}

.mailbox-reader-attachment > i:first-child {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  color: #0877b7;
  background: #e6f5fb;
}

.mailbox-reader-attachment > span {
  min-width: 0;
}

.mailbox-reader-attachment b,
.mailbox-reader-attachment small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-reader-attachment b {
  color: #102a43;
  font-size: 0.84rem;
}

.mailbox-reader-attachment small {
  color: #64748b;
  font-size: 0.72rem;
}

.mailbox-reader-attachment > i:last-child {
  color: #0f5f76;
}

.mailbox-reader-body {
  min-height: 420px;
  padding: 20px;
  color: #1f2937;
  font-size: 0.94rem;
  line-height: 1.65;
  overflow: auto;
  overflow-wrap: anywhere;
}

.mailbox-body-block {
  max-width: 980px;
  margin: 0 0 14px;
}

.mailbox-body-block:last-child {
  margin-bottom: 0;
}

.mailbox-body-block > div + div {
  margin-top: 4px;
}

.mailbox-body-paragraph {
  color: #1f2937;
}

.mailbox-body-quote {
  margin-top: 18px;
  padding: 12px 14px;
  border-left: 3px solid #bfd7e3;
  border-radius: 0 8px 8px 0;
  color: #64748b;
  background: #f8fafc;
  font-size: 0.9em;
}

.mailbox-body-signature {
  margin-top: 22px;
  padding-top: 14px;
  border-top: 1px solid #dbe8ef;
  color: #64748b;
  font-size: 0.92em;
}

.mailbox-body-empty {
  margin: 0;
  color: #64748b;
  font-weight: 800;
}

.mailbox-reader-body :deep(a) {
  color: #0369a1;
  font-weight: 800;
  text-decoration: none;
  border-bottom: 1px solid rgba(3, 105, 161, 0.28);
}

.mailbox-reader-body :deep(a:hover) {
  color: #0f5f76;
  border-bottom-color: currentColor;
}

.mailbox-settings {
  padding: 22px;
}

.mailbox-settings-head,
.mailbox-compose-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 18px;
}

.mailbox-settings p {
  margin: 4px 0 0;
  color: #64748b;
}

.mailbox-settings-form {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.mailbox-settings-form label,
.mailbox-compose-form label {
  display: grid;
  gap: 6px;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 800;
}

.mailbox-settings-form input,
.mailbox-settings-form select,
.mailbox-settings-form textarea,
.mailbox-compose-form input {
  min-height: 42px;
  padding: 0 12px;
}

.mailbox-settings-form textarea {
  min-height: 178px;
  padding: 12px;
  resize: vertical;
}

.mailbox-compose-form textarea {
  min-height: 260px;
  padding: 12px;
  resize: vertical;
}

.mailbox-settings-form input:focus,
.mailbox-settings-form select:focus,
.mailbox-settings-form textarea:focus,
.mailbox-compose-form input:focus,
.mailbox-compose-form textarea:focus,
.mailbox-search input:focus,
.mailbox-search-scope:focus,
.mailbox-move select:focus {
  border-color: #0786b1;
  box-shadow: 0 0 0 3px rgba(7, 134, 177, 0.12);
}

.mailbox-settings-actions,
.mailbox-alert {
  grid-column: 1 / -1;
}

.mailbox-settings-signature {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
  gap: 14px;
  align-items: stretch;
}

.mailbox-signature-preview {
  display: grid;
  grid-template-rows: auto 1fr;
  gap: 10px;
  min-width: 0;
  padding: 12px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  background: linear-gradient(180deg, #fbfdfe 0%, #f3f9fb 100%);
}

.mailbox-signature-preview-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  color: #64748b;
  font-size: .78rem;
  font-weight: 900;
  text-transform: uppercase;
}

.mailbox-signature-preview-head button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 32px;
  padding: 0 10px;
  border: 1px solid #b8d7e2;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-size: .78rem;
  font-weight: 900;
  text-transform: none;
}

.mailbox-signature-card {
  align-self: stretch;
  padding: 14px 16px;
  border-left: 3px solid #0ea5e9;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  box-shadow: 0 12px 28px rgba(15, 95, 118, .08);
}

.mailbox-signature-greeting {
  margin-bottom: 10px;
  color: #64748b;
  font-size: .88rem;
  font-weight: 700;
}

.mailbox-signature-name {
  color: #075985;
  font-size: 1rem;
  font-weight: 950;
}

.mailbox-signature-role {
  margin-top: 3px;
  color: #102a43;
  font-size: .88rem;
  font-weight: 800;
}

.mailbox-signature-detail {
  margin-top: 4px;
  color: #64748b;
  font-size: .82rem;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.mailbox-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 8px;
  font-weight: 700;
}

.mailbox-alert-danger {
  color: #9f1239;
  background: #fff1f2;
  border: 1px solid #fecdd3;
}

.mailbox-alert-inline {
  margin: 10px 12px 0;
}

.mailbox-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px;
  margin-top: auto;
  border-top: 1px solid #e0edf2;
  background: #fff;
}

.mailbox-pagination button {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
}

.mailbox-loading,
.mailbox-empty,
.mailbox-reader-empty {
  display: grid;
  place-items: center;
  gap: 12px;
  min-height: 240px;
  color: #64748b;
  text-align: center;
}

.mailbox-loading-page {
  min-height: calc(100vh - 140px);
}

.mailbox-loading-panel {
  min-height: 420px;
}

.mailbox-empty i,
.mailbox-reader-empty i {
  color: #9ccbd8;
  font-size: 2.4rem;
}

.mailbox-empty h3,
.mailbox-reader-empty h3 {
  margin: 0;
  color: #334155;
  font-size: 1rem;
  font-weight: 900;
}

.mailbox-empty p,
.mailbox-reader-empty p {
  margin: 0;
}

.mailbox-compose-backdrop {
  position: fixed;
  top: var(--mailbox-compose-top, 76px);
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 6000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow: auto;
  padding: 14px;
  background: rgba(11, 31, 46, 0.42);
  backdrop-filter: blur(6px);
}

.mailbox-compose-drawer {
  display: flex;
  width: min(900px, calc(100vw - 28px));
  max-height: calc(100dvh - var(--mailbox-compose-top, 76px) - 28px);
  flex-direction: column;
  overflow: hidden;
  padding: 0;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 26px 80px rgba(11, 31, 46, 0.34);
}

.mailbox-compose-head {
  margin: 0;
  padding: 16px 18px;
  color: #fff;
  background: linear-gradient(135deg, #04769a 0%, #168b7f 100%);
}

.mailbox-compose-head .mailbox-kicker,
.mailbox-compose-head h2 {
  color: #fff;
}

.mailbox-compose-head .mailbox-icon-btn {
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
}

.mailbox-compose-form {
  display: grid;
  gap: 14px;
  min-height: 0;
  overflow: auto;
  padding: 16px 18px 18px;
}

.mailbox-compose-recipients {
  display: grid;
  gap: 8px;
  padding: 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  background: #f8fbfc;
}

.mailbox-recipient-row {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
}

.mailbox-recipient-label {
  padding-top: 9px;
  color: #0f5f76;
  font-size: 0.78rem;
  font-weight: 900;
  text-align: center;
}

.mailbox-recipient-box {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  min-height: 40px;
  padding: 5px 8px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  background: #fff;
}

.mailbox-recipient-box:focus-within {
  border-color: #0786b1;
  box-shadow: 0 0 0 3px rgba(7, 134, 177, 0.12);
}

.mailbox-recipient-box input {
  flex: 1;
  min-width: 180px;
  min-height: 28px;
  padding: 0;
  border: 0;
  box-shadow: none;
}

.mailbox-recipient-box input:focus {
  box-shadow: none;
}

.mailbox-recipient-chip {
  display: inline-flex;
  align-items: center;
  max-width: 260px;
  gap: 6px;
  min-height: 28px;
  padding: 0 6px 0 10px;
  border-radius: 999px;
  color: #075985;
  background: #e0f2fe;
  font-size: 0.82rem;
  font-weight: 800;
}

.mailbox-recipient-chip button {
  display: grid;
  place-items: center;
  width: 20px;
  height: 20px;
  border: 0;
  border-radius: 999px;
  color: #075985;
  background: transparent;
}

.mailbox-recipient-chip button:hover {
  background: rgba(7, 89, 133, 0.12);
}

.mailbox-smart-panel {
  display: grid;
  gap: 10px;
  padding: 10px;
  border: 1px solid #d7e8ee;
  border-radius: 8px;
  background: #fbfdfe;
}

.mailbox-smart-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #64748b;
  font-size: 0.82rem;
}

.mailbox-smart-head strong {
  color: #102a43;
}

.mailbox-smart-groups {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.mailbox-smart-groups button {
  display: inline-grid;
  grid-template-columns: 18px minmax(0, auto) auto;
  gap: 7px;
  align-items: center;
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid #cfe1e8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 800;
}

.mailbox-smart-groups button:disabled {
  opacity: 0.45;
}

.mailbox-smart-groups button b {
  min-width: 22px;
  padding: 2px 7px;
  border-radius: 999px;
  color: #fff;
  background: #0b83a5;
  font-size: 0.72rem;
}

.mailbox-contact-suggestions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.mailbox-contact-suggestions button {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 8px;
  align-items: center;
  min-height: 46px;
  padding: 7px;
  border: 1px solid #e0edf2;
  border-radius: 8px;
  background: #fff;
  color: #172033;
  text-align: left;
}

.mailbox-contact-suggestions button:hover {
  border-color: #8fd3e8;
  background: #f0fbff;
}

.mailbox-contact-avatar {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  color: #fff;
  background: #04769a;
  font-size: 0.82rem;
  font-weight: 900;
}

.mailbox-contact-suggestions strong,
.mailbox-contact-suggestions small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-contact-suggestions strong {
  font-size: 0.84rem;
}

.mailbox-contact-suggestions small {
  color: #64748b;
  font-size: 0.74rem;
}

.mailbox-attachments {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border: 1px dashed #b8d7e2;
  border-radius: 8px;
  background: #f8fbfc;
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 700;
}

.mailbox-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.mailbox-attach-btn {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #9ccbd8;
  border-radius: 8px;
  color: #0f5f76;
  background: #fff;
  font-weight: 900;
}

.mailbox-attach-btn:hover {
  border-color: #0786b1;
  background: #eef9fc;
}

.mailbox-attachment-list {
  display: grid;
  gap: 8px;
}

.mailbox-attachment-item {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr) auto 30px;
  align-items: center;
  gap: 8px;
  min-height: 40px;
  padding: 6px 8px;
  border: 1px solid #e0edf2;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  font-size: 0.84rem;
  font-weight: 800;
}

.mailbox-attachment-item span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mailbox-attachment-item small {
  color: #64748b;
  font-size: 0.74rem;
  white-space: nowrap;
}

.mailbox-attachment-item button {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: 8px;
  color: #64748b;
  background: #eef4f7;
}

.mailbox-attachment-item button:hover {
  color: #9f1239;
  background: #ffe4e6;
}

.mailbox-compose-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.mailbox-ghost-light-btn {
  padding: 0 16px;
}

@media (min-width: 861px) {
  .mailbox-page {
    height: 100%;
    min-height: 0;
    padding: clamp(10px, 1.2vw, 18px);
    background:
      radial-gradient(circle at 16% 0%, rgba(14, 165, 233, .12), transparent 32%),
      radial-gradient(circle at 88% 10%, rgba(20, 184, 166, .1), transparent 28%),
      linear-gradient(180deg, #eef8fc 0%, #f8fbfd 100%);
  }

  .outlook-shell,
  .mailbox-settings {
    max-width: none;
    border-color: rgba(148, 163, 184, .32);
    border-radius: 18px;
    box-shadow: 0 24px 60px rgba(15, 76, 92, .16);
  }

  .outlook-shell {
    grid-template-columns: 280px minmax(0, 1fr);
    height: 100%;
    min-height: 0;
    max-height: none;
    overflow: hidden;
    background: rgba(255, 255, 255, .92);
  }

  .mailbox-settings {
    max-height: 100%;
    overflow: auto;
  }

  .mailbox-sidebar {
    gap: 16px;
    padding: 16px;
    background:
      linear-gradient(180deg, rgba(255,255,255,.96), rgba(240,249,252,.96));
    border-right-color: rgba(203, 213, 225, .72);
  }

  .mailbox-account,
  .mailbox-side-card {
    border: 1px solid rgba(203, 213, 225, .72);
    border-radius: 14px;
    background: rgba(255, 255, 255, .88);
    box-shadow: 0 10px 24px rgba(15, 76, 92, .08);
  }

  .mailbox-account {
    padding: 12px;
  }

  .mailbox-account-avatar,
  .mailbox-avatar {
    border-radius: 12px;
    background: linear-gradient(135deg, #dff7ff, #d9fbe8);
  }

  .mailbox-compose-btn {
    min-height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #04769a, #0f8b7f);
    box-shadow: 0 14px 30px rgba(4, 118, 154, .22);
  }

  .mailbox-folder-nav {
    gap: 5px;
  }

  .mailbox-folder-btn {
    min-height: 42px;
    padding: 0 10px;
    border-radius: 12px;
    font-weight: 750;
  }

  .mailbox-folder-btn i {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 9px;
    color: #0f6f88;
    background: rgba(224, 242, 254, .72);
  }

  .mailbox-folder-btn:hover,
  .mailbox-folder-btn.active {
    border-color: rgba(125, 211, 252, .78);
    background: #fff;
    box-shadow: 0 10px 22px rgba(14, 116, 144, .1);
  }

  .mailbox-folder-btn.active i {
    color: #fff;
    background: #0877b7;
  }

  .mailbox-main {
    background: #f8fbfd;
  }

  .mailbox-topbar {
    min-height: 82px;
    padding: 18px 22px;
    background: rgba(255, 255, 255, .94);
    border-bottom-color: rgba(203, 213, 225, .72);
  }

  .mailbox-topbar h1 {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 1.34rem;
    letter-spacing: 0;
  }

  .mailbox-search {
    max-width: min(580px, 48vw);
  }

  .mailbox-search input {
    min-height: 44px;
    border-radius: 12px;
    background: #f8fafc;
  }

  .mailbox-commandbar {
    gap: 9px;
    padding: 12px 18px;
    background: rgba(248, 251, 253, .96);
    border-bottom-color: rgba(203, 213, 225, .72);
  }

  .mailbox-command,
  .mailbox-move select,
  .mailbox-icon-btn {
    min-height: 38px;
    border-radius: 11px;
  }

  .mailbox-command.settings {
    margin-left: auto;
  }

  .mailbox-content {
    grid-template-columns: minmax(340px, 430px) minmax(0, 1fr);
    gap: 0;
    min-height: 0;
    overflow: hidden;
    background: #f4f8fb;
  }

  .mailbox-list-pane {
    background: #f7fbfd;
    border-right-color: rgba(203, 213, 225, .72);
  }

  .mailbox-list-head {
    padding: 12px 16px;
    background: rgba(255, 255, 255, .82);
    border-bottom-color: rgba(203, 213, 225, .72);
  }

  .mailbox-message-list {
    display: grid;
    gap: 8px;
    padding: 10px;
  }

  .mailbox-message-item {
    border: 1px solid rgba(203, 213, 225, .72);
    border-radius: 14px;
    background: rgba(255, 255, 255, .88);
    box-shadow: 0 8px 18px rgba(15, 76, 92, .045);
  }

  .mailbox-message-item.unread {
    background: #fff;
    border-color: rgba(14, 165, 233, .38);
  }

  .mailbox-message-item:hover,
  .mailbox-message-item.active {
    border-color: rgba(14, 165, 233, .58);
    background: #fff;
    box-shadow: 0 14px 28px rgba(14, 116, 144, .12);
  }

  .mailbox-reader {
    min-height: 0;
    margin: 12px;
    overflow: hidden;
    border: 1px solid rgba(203, 213, 225, .72);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 14px 32px rgba(15, 76, 92, .07);
  }

  .mailbox-reader-head {
    padding: 22px 26px 18px;
    border-bottom-color: rgba(203, 213, 225, .72);
  }

  .mailbox-reader-head h2 {
    max-width: 960px;
    font-size: clamp(1.2rem, 1.6vw, 1.55rem);
    line-height: 1.38;
  }

  .mailbox-reader-meta {
    max-width: 980px;
    line-height: 1.7;
  }

  .mailbox-reader-actions .mailbox-icon-btn {
    background: #eef8fd;
  }

  .mailbox-reader-tools {
    padding: 12px 26px;
    background: #fbfdfe;
    border-bottom-color: rgba(203, 213, 225, .72);
  }

  .mailbox-reader-tools button {
    min-height: 36px;
    border-radius: 999px;
  }

  .mailbox-reader-attachments {
    padding: 14px 26px;
  }

  .mailbox-reader-body {
    flex: 1;
    min-height: 0;
    max-width: 1040px;
    padding: 28px 32px 38px;
    color: #1e293b;
    font-size: .98rem;
    line-height: 1.76;
  }
}

@media (max-width: 1040px) {
  .outlook-shell {
    grid-template-columns: 238px minmax(0, 1fr);
  }

  .mailbox-content {
    grid-template-columns: minmax(300px, 370px) minmax(0, 1fr);
  }

  .mailbox-compose-drawer {
    width: min(820px, calc(100vw - 24px));
  }

  .mailbox-smart-groups button {
    flex: 1 1 180px;
  }
}

@media (min-width: 861px) and (max-height: 720px) {
  .mailbox-page {
    min-height: 0;
    padding: 4px 8px 8px;
  }

  .outlook-shell {
    height: 100%;
    max-height: none;
    border-radius: 10px;
  }

  .outlook-shell {
    grid-template-columns: 228px minmax(0, 1fr);
  }

  .mailbox-sidebar {
    gap: 8px;
    padding: 10px;
  }

  .mailbox-account {
    grid-template-columns: 38px minmax(0, 1fr);
    padding: 7px 8px;
  }

  .mailbox-account-avatar {
    width: 38px;
    height: 38px;
    font-size: .86rem;
  }

  .mailbox-account span,
  .mailbox-nav-title {
    font-size: .66rem;
  }

  .mailbox-account strong {
    font-size: .78rem;
  }

  .mailbox-compose-btn {
    min-height: 36px;
    font-size: .82rem;
  }

  .mailbox-folder-btn {
    min-height: 34px;
    padding: 0 8px;
    font-size: .82rem;
  }

  .mailbox-folder-btn i {
    width: 24px;
    height: 24px;
    border-radius: 8px;
    font-size: .82rem;
  }

  .mailbox-folder-btn b {
    min-width: 20px;
    padding: 1px 6px;
    font-size: .66rem;
  }

  .mailbox-side-card {
    display: none;
  }

  .mailbox-topbar {
    min-height: 52px;
    padding: 8px 14px;
  }

  .mailbox-topbar h1 {
    font-size: 1rem;
  }

  .mailbox-kicker {
    font-size: .66rem;
  }

  .mailbox-search {
    max-width: min(500px, 46vw);
  }

  .mailbox-search input,
  .mailbox-search-scope {
    min-height: 34px;
    font-size: .8rem;
  }

  .mailbox-search-scope {
    width: 108px;
  }

  .mailbox-commandbar {
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 6px 10px;
    scrollbar-width: none;
  }

  .mailbox-commandbar::-webkit-scrollbar {
    display: none;
  }

  .mailbox-command,
  .mailbox-move select,
  .mailbox-icon-btn {
    flex: 0 0 auto;
    min-height: 32px;
    border-radius: 9px;
    font-size: .76rem;
  }

  .mailbox-command {
    padding: 0 8px;
  }

  .mailbox-command.primary {
    font-size: .8rem;
  }

  .mailbox-command.settings,
  .mailbox-icon-btn {
    width: 34px;
    padding: 0;
  }

  .mailbox-command.settings {
    margin-left: 0;
  }

  .mailbox-selection-status,
  .mailbox-move {
    flex: 0 0 auto;
  }

  .mailbox-selection-status {
    max-width: 170px;
    min-height: 32px;
    font-size: .74rem;
  }

  .mailbox-move select {
    min-width: 138px;
    max-width: 150px;
    padding: 0 8px;
  }

  .mailbox-content {
    grid-template-columns: minmax(300px, 420px) minmax(0, 1fr);
  }

  .mailbox-list-head {
    padding: 8px 12px;
    font-size: .76rem;
  }

  .mailbox-message-list {
    gap: 6px;
    padding: 8px;
  }

  .mailbox-message-item {
    grid-template-columns: 28px 38px minmax(0, 1fr);
    gap: 8px;
    min-height: 70px;
    padding: 9px 10px;
    border-radius: 12px;
  }

  .mailbox-avatar {
    width: 38px;
    height: 38px;
    font-size: .88rem;
  }

  .mailbox-message-top strong {
    font-size: .82rem;
  }

  .mailbox-message-top time,
  .mailbox-message-meta {
    font-size: .72rem;
  }

  .mailbox-subject {
    font-size: .82rem;
  }

  .mailbox-reader {
    margin: 6px;
    border-radius: 12px;
  }

  .mailbox-reader-head {
    gap: 10px;
    padding: 12px 16px 10px;
  }

  .mailbox-reader-head h2 {
    margin-bottom: 6px;
    font-size: .98rem;
    line-height: 1.28;
  }

  .mailbox-reader-meta {
    line-height: 1.45;
    font-size: .76rem;
  }

  .mailbox-reader-actions {
    gap: 6px;
  }

  .mailbox-reader-actions .mailbox-icon-btn {
    width: 34px;
    min-height: 34px;
    font-size: .82rem;
  }

  .mailbox-reader-tools {
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 6px 16px;
    scrollbar-width: none;
  }

  .mailbox-reader-tools::-webkit-scrollbar {
    display: none;
  }

  .mailbox-reader-tools button {
    flex: 0 0 auto;
    min-height: 30px;
    padding: 0 8px;
    font-size: .74rem;
  }

  .mailbox-reader-attachments {
    padding: 8px 16px;
  }

  .mailbox-reader-body {
    min-height: 0;
    padding: 14px 18px 22px;
    font-size: .88rem;
    line-height: 1.58;
  }

  .mailbox-body-quote {
    padding: 10px 12px;
    font-size: .86em;
  }
}

@media (max-width: 860px) {
  .mailbox-page {
    padding: 10px;
    overflow: visible;
  }

  .outlook-shell {
    grid-template-columns: 1fr;
    overflow: visible;
  }

  .mailbox-sidebar {
    gap: 10px;
    padding: 10px;
    border-right: 0;
    border-bottom: 1px solid #dbeaf0;
  }

  .mailbox-folder-nav {
    flex-direction: row;
    padding-bottom: 4px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }

  .mailbox-folder-nav::-webkit-scrollbar,
  .mailbox-commandbar::-webkit-scrollbar {
    display: none;
  }

  .mailbox-nav-title,
  .mailbox-side-card {
    display: none;
  }

  .mailbox-folder-btn {
    min-width: 150px;
    flex: 0 0 auto;
  }

  .mailbox-topbar,
  .mailbox-reader-head,
  .mailbox-settings-head,
  .mailbox-compose-head {
    align-items: stretch;
    flex-direction: column;
  }

  .mailbox-search {
    max-width: none;
  }

  .mailbox-content {
    display: block;
  }

  .mailbox-list-pane {
    border-right: 0;
    border-bottom: 0;
  }

  .mailbox-content.mailbox-content-reading .mailbox-list-pane,
  .mailbox-content:not(.mailbox-content-reading) .mailbox-reader {
    display: none;
  }

  .mailbox-reader {
    min-height: calc(100dvh - 220px);
  }

  .mailbox-reader-back {
    display: inline-flex;
    align-items: center;
    width: max-content;
    gap: 8px;
    min-height: 34px;
    margin-bottom: 10px;
    padding: 0 12px;
    border: 1px solid #cfe1e8;
    border-radius: 8px;
    color: #0f5f76;
    background: #fff;
    font-weight: 900;
  }

  .mailbox-settings-form {
    grid-template-columns: 1fr;
  }

  .mailbox-settings-signature {
    grid-template-columns: 1fr;
  }

  .mailbox-compose-backdrop {
    top: 66px;
    padding: 10px;
  }

  .mailbox-compose-head {
    align-items: center;
    flex-direction: row;
  }

  .mailbox-compose-drawer {
    width: min(720px, calc(100vw - 20px));
    max-height: calc(100dvh - 86px);
  }

  .mailbox-contact-suggestions {
    grid-template-columns: 1fr;
  }

  .mailbox-recipient-row {
    grid-template-columns: 36px minmax(0, 1fr);
  }
}

@media (max-width: 620px) {
  .mailbox-page {
    min-height: calc(100dvh - 64px);
    padding: 8px;
  }

  .outlook-shell,
  .mailbox-settings {
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }

  .outlook-shell {
    min-height: calc(100dvh - 80px);
    gap: 8px;
  }

  .mailbox-sidebar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 44px;
    gap: 8px;
    padding: 0;
    border-bottom: 0;
    background: transparent;
  }

  .mailbox-account {
    min-width: 0;
    grid-template-columns: 36px minmax(0, 1fr);
    padding: 8px;
  }

  .mailbox-account-avatar {
    width: 36px;
    height: 36px;
  }

  .mailbox-account span {
    font-size: 0.68rem;
  }

  .mailbox-account strong {
    font-size: 0.82rem;
  }

  .mailbox-compose-btn {
    width: 44px;
    min-height: 44px;
    align-self: stretch;
    padding: 0;
    font-size: 0;
  }

  .mailbox-compose-btn i {
    font-size: 1rem;
  }

  .mailbox-folder-nav {
    grid-column: 1 / -1;
    gap: 6px;
    padding: 0 0 2px;
  }

  .mailbox-folder-btn {
    display: inline-flex;
    min-width: 0;
    min-height: 38px;
    gap: 8px;
    padding: 0 10px;
    background: #fff;
    border-color: #d8e9ef;
  }

  .mailbox-folder-btn span {
    max-width: 112px;
  }

  .mailbox-folder-btn b {
    min-width: 24px;
  }

  .mailbox-main {
    overflow: hidden;
    border: 1px solid #d8e9ef;
    border-radius: 8px;
  }

  .mailbox-topbar {
    gap: 8px;
    padding: 10px;
  }

  .mailbox-topbar h1 {
    font-size: 1.02rem;
  }

  .mailbox-search input {
    min-height: 38px;
    font-size: 0.9rem;
  }

  .mailbox-commandbar {
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    padding: 8px;
    -webkit-overflow-scrolling: touch;
  }

  .mailbox-command {
    min-height: 36px;
    flex: 0 0 auto;
    padding: 0 10px;
    white-space: nowrap;
  }

  .mailbox-command.settings {
    margin-left: 0;
  }

  .mailbox-move {
    flex: 0 0 auto;
  }

  .mailbox-move select {
    min-width: 150px;
    max-width: 170px;
  }

  .mailbox-list-head {
    padding: 9px 10px;
  }

  .mailbox-message-list {
    overflow: visible;
  }

  .mailbox-message-item {
    grid-template-columns: 30px 38px minmax(0, 1fr);
    gap: 9px;
    min-height: 76px;
    padding: 11px 10px;
  }

  .mailbox-avatar {
    width: 38px;
    height: 38px;
  }

  .mailbox-message-top {
    gap: 8px;
  }

  .mailbox-message-top strong {
    font-size: 0.86rem;
  }

  .mailbox-subject {
    font-size: 0.84rem;
  }

  .mailbox-reader-head {
    gap: 10px;
    padding: 12px;
  }

  .mailbox-reader-head h2 {
    font-size: 1rem;
    line-height: 1.3;
  }

  .mailbox-reader-meta {
    display: grid;
    gap: 4px;
    font-size: 0.78rem;
  }

  .mailbox-reader-actions {
    flex-wrap: wrap;
  }

  .mailbox-reader-tools {
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    padding: 8px 10px;
  }

  .mailbox-reader-tools button {
    flex: 0 0 auto;
    white-space: nowrap;
  }

  .mailbox-reader-body {
    min-height: calc(100dvh - 260px);
    padding: 14px;
    font-size: 0.9rem;
    line-height: 1.55;
  }

  .mailbox-pagination {
    padding: 10px;
  }

  .mailbox-compose-backdrop {
    top: 64px;
    padding: 8px;
  }

  .mailbox-compose-drawer {
    width: calc(100vw - 16px);
    max-height: calc(100dvh - 80px);
  }

  .mailbox-compose-head {
    padding: 12px;
  }

  .mailbox-compose-form {
    gap: 11px;
    padding: 12px;
  }

  .mailbox-compose-form textarea {
    min-height: 190px;
  }

  .mailbox-smart-groups {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .mailbox-smart-groups button {
    grid-template-columns: 18px minmax(0, 1fr) auto;
    min-width: 0;
    padding: 0 8px;
  }

  .mailbox-smart-groups button span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mailbox-attachments {
    align-items: stretch;
    flex-direction: column;
  }

  .mailbox-attach-btn {
    justify-content: center;
  }

  .mailbox-attachment-item {
    grid-template-columns: 20px minmax(0, 1fr) 28px;
  }

  .mailbox-attachment-item small {
    display: none;
  }
}

.mailbox-mobile-header,
.mailbox-mobile-filterbar,
.mailbox-mobile-search,
.mailbox-mobile-tabbar,
.mailbox-mobile-compose-fab,
.mailbox-mobile-menu-backdrop {
  display: none;
}

@media (max-width: 860px) {
  .mailbox-page {
    min-height: calc(100dvh - 64px);
    margin-inline: calc(var(--bs-gutter-x, 1.5rem) * -0.5);
    padding: 0;
    background: #fff;
    overflow: visible;
  }

  .outlook-shell,
  .mailbox-settings {
    max-width: none;
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .outlook-shell {
    display: block;
    min-height: calc(100dvh - 64px);
    overflow: visible;
    background: #fff;
  }

  .mailbox-sidebar,
  .mailbox-topbar,
  .mailbox-commandbar {
    display: none;
  }

  .mailbox-main {
    min-height: calc(100dvh - 64px);
    padding-bottom: 82px;
    overflow: visible;
    border: 0;
    border-radius: 0;
    background: #fff;
  }

  .mailbox-mobile-header {
    position: sticky;
    top: 0;
    z-index: 30;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 40px 40px;
    gap: 10px;
    align-items: center;
    min-height: 74px;
    padding: 12px 16px 10px;
    color: #fff;
    background: #0877b7;
  }

  .mailbox-mobile-avatar-btn,
  .mailbox-mobile-action,
  .mailbox-mobile-menu-close,
  .mailbox-mobile-rail-btn,
  .mailbox-mobile-rail-avatar {
    display: inline-grid;
    place-items: center;
    border: 0;
    cursor: pointer;
  }

  .mailbox-mobile-avatar-btn,
  .mailbox-mobile-action {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
  }

  .mailbox-mobile-avatar-btn span {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border-radius: 999px;
    background: #d97706;
    font-weight: 900;
  }

  .mailbox-mobile-heading {
    min-width: 0;
  }

  .mailbox-mobile-heading span {
    display: block;
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
  }

  .mailbox-mobile-heading h1 {
    overflow: hidden;
    margin: 1px 0 0;
    color: #fff;
    font-size: 1.28rem;
    font-weight: 900;
    line-height: 1.15;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mailbox-mobile-filterbar {
    position: sticky;
    top: 74px;
    z-index: 28;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 0 16px 12px;
    color: #fff;
    background: #0877b7;
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
  }

  .mailbox-mobile-pills {
    display: inline-flex;
    min-width: 0;
    padding: 3px;
    border-radius: 999px;
    background: rgba(8, 47, 73, 0.34);
  }

  .mailbox-mobile-pills button,
  .mailbox-mobile-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 32px;
    border: 0;
    border-radius: 999px;
    font-weight: 900;
  }

  .mailbox-mobile-pills button {
    min-width: 86px;
    padding: 0 12px;
    color: #d9f0fb;
    background: transparent;
  }

  .mailbox-mobile-pills button.active {
    color: #0877b7;
    background: #fff;
  }

  .mailbox-mobile-pills b {
    min-width: 22px;
    padding: 2px 6px;
    border-radius: 999px;
    color: #fff;
    background: #0877b7;
    font-size: 0.7rem;
  }

  .mailbox-mobile-filter {
    flex: 0 0 auto;
    padding: 0 2px;
    color: #fff;
    background: transparent;
    font-size: 0.95rem;
  }

  .mailbox-mobile-search {
    position: sticky;
    top: 118px;
    z-index: 27;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid #e5eef4;
    background: #fff;
  }

  .mailbox-mobile-search .mailbox-search-field {
    min-width: 0;
  }

  .mailbox-mobile-search .mailbox-search-scope {
    flex: 0 0 124px;
    min-height: 42px;
  }

  .mailbox-mobile-search i {
    color: #0877b7;
  }

  .mailbox-mobile-search input {
    min-width: 0;
    width: 100%;
    min-height: 42px;
    border: 0;
    outline: none;
    color: #111827;
    background: transparent;
    font-size: 1rem;
  }

  .mailbox-main-reading .mailbox-mobile-filterbar,
  .mailbox-main-reading .mailbox-mobile-search {
    display: none;
  }

  .mailbox-main.mailbox-main-reading {
    height: calc(100dvh - 64px);
    min-height: 0;
    padding-bottom: 0;
    overflow: hidden;
  }

  .mailbox-content {
    display: block;
    flex: 1;
    min-height: calc(100dvh - 150px);
  }

  .mailbox-main-reading .mailbox-content {
    min-height: 0;
    overflow: hidden;
  }

  .mailbox-list-pane {
    border: 0;
    background: #fff;
  }

  .mailbox-list-head {
    display: none;
  }

  .mailbox-message-list {
    overflow: visible;
    background: #fff;
  }

  .mailbox-message-item {
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 8px;
    min-height: 0;
    padding: 14px 22px;
    border-bottom: 0;
    color: #111827;
    background: #fff;
  }

  .mailbox-message-item + .mailbox-message-item {
    border-top: 1px solid #eef2f6;
  }

  .mailbox-message-item:hover,
  .mailbox-message-item.active {
    background: #f4f9fc;
    box-shadow: none;
  }

  .mailbox-avatar {
    display: none;
  }

  .mailbox-message-top strong {
    color: #374151;
    font-size: 1rem;
    font-weight: 650;
  }

  .mailbox-message-top time {
    flex: 0 0 auto;
    color: #4b5563;
    font-size: 0.9rem;
  }

  .mailbox-subject {
    margin-top: 4px;
    color: #4b5563;
    font-size: 0.95rem;
    font-weight: 500;
  }

  .mailbox-message-item.unread {
    background: #f8fcff;
  }

  .mailbox-message-item.unread::before {
    top: 16px;
    bottom: 16px;
  }

  .mailbox-message-item.unread .mailbox-message-top strong {
    color: #071b2c;
    font-weight: 900;
  }

  .mailbox-message-item.unread .mailbox-subject {
    color: #111827;
    font-weight: 800;
  }

  .mailbox-message-item.unread .mailbox-message-top time,
  .mailbox-message-item.unread .mailbox-message-meta {
    color: #172033;
    font-weight: 800;
  }

  .mailbox-message-meta {
    margin-top: 3px;
    color: #6b7280;
    font-size: 0.9rem;
  }

  .mailbox-pagination {
    border-top: 0;
    padding: 16px 18px 22px;
    background: #fff;
  }

  .mailbox-pagination span {
    color: #0877b7;
    font-weight: 900;
  }

  .mailbox-content.mailbox-content-reading .mailbox-list-pane,
  .mailbox-content:not(.mailbox-content-reading) .mailbox-reader {
    display: none;
  }

  .mailbox-reader {
    min-height: calc(100dvh - 138px);
    background: #fff;
  }

  .mailbox-main-reading .mailbox-reader {
    height: 100%;
    min-height: 0;
    overflow-y: auto;
  }

  .mailbox-reader-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    padding: 14px 16px;
  }

  .mailbox-reader-back {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: max-content;
    min-height: 32px;
    margin-bottom: 10px;
    padding: 0;
    border: 0;
    color: #0877b7;
    background: transparent;
    font-weight: 900;
  }

  .mailbox-reader-head h2 {
    overflow-wrap: anywhere;
    color: #111827;
    font-size: 1.04rem;
    line-height: 1.3;
  }

  .mailbox-reader-meta {
    display: grid;
    gap: 4px;
    font-size: 0.82rem;
  }

  .mailbox-reader-meta-line {
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    gap: 8px;
  }

  .mailbox-reader-actions {
    flex-wrap: nowrap;
  }

  .mailbox-reader-tools {
    flex-wrap: nowrap;
    gap: 10px;
    min-height: 54px;
    overflow-x: auto;
    padding: 9px 16px;
    background: #f8fafc;
    -webkit-overflow-scrolling: touch;
  }

  .mailbox-reader-tools::-webkit-scrollbar {
    display: none;
  }

  .mailbox-reader-tools button {
    flex: 0 0 auto;
    justify-content: center;
    width: 42px;
    min-width: 42px;
    min-height: 38px;
    padding: 0;
    border-radius: 12px;
    font-size: 0;
    white-space: nowrap;
  }

  .mailbox-reader-tools button i {
    font-size: .98rem;
  }

  .mailbox-reader-attachments {
    padding: 12px 16px;
  }

  .mailbox-reader-attachment-list {
    grid-template-columns: 1fr;
  }

  .mailbox-reader-body {
    min-height: calc(100dvh - 250px);
    padding: 18px 16px 28px;
    color: #111827;
    font-size: 0.96rem;
    line-height: 1.65;
  }

  .mailbox-main-reading .mailbox-reader-body {
    padding-bottom: calc(96px + env(safe-area-inset-bottom));
  }

  .mailbox-mobile-compose-fab {
    position: fixed;
    right: 18px;
    bottom: 88px;
    z-index: 1200;
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 16px;
    color: #0877b7;
    background: #fff;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.2);
  }

  .mailbox-mobile-tabbar {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1100;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    min-height: 72px;
    padding: 8px 12px calc(8px + env(safe-area-inset-bottom));
    border-top: 1px solid #e5edf3;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 -10px 28px rgba(15, 23, 42, 0.08);
  }

  .mailbox-mobile-tabbar button,
  .mailbox-mobile-tabbar a {
    display: grid;
    place-items: center;
    gap: 3px;
    min-width: 0;
    border: 0;
    color: #64748b;
    background: transparent;
    text-decoration: none;
    font-size: 0.74rem;
    font-weight: 800;
  }

  .mailbox-mobile-tabbar i {
    font-size: 1.1rem;
  }

  .mailbox-mobile-tabbar .active {
    color: #0877b7;
  }

  .mailbox-mobile-menu-backdrop {
    --mailbox-mobile-nav-offset: 66px;
    position: fixed;
    top: var(--mailbox-mobile-nav-offset);
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 6500;
    display: flex;
    align-items: stretch;
    background: rgba(15, 23, 42, 0.46);
  }

  .mailbox-mobile-menu {
    display: grid;
    grid-template-columns: 78px minmax(0, 1fr);
    width: min(92vw, 420px);
    max-width: 100%;
    height: calc(100dvh - var(--mailbox-mobile-nav-offset));
    min-height: 0;
    max-height: calc(100dvh - var(--mailbox-mobile-nav-offset));
    overflow: hidden;
    background: #fff;
    box-shadow: 22px 0 48px rgba(15, 23, 42, 0.22);
  }

  .mailbox-mobile-menu-rail {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 18px 10px;
    min-height: 0;
    overflow-y: auto;
    background: #eef2f5;
  }

  .mailbox-mobile-rail-btn,
  .mailbox-mobile-rail-avatar {
    width: 50px;
    height: 50px;
    border-radius: 999px;
    color: #64748b;
    background: #fff;
    font-weight: 900;
  }

  .mailbox-mobile-rail-btn.active,
  .mailbox-mobile-rail-avatar {
    color: #fff;
    background: #0877b7;
  }

  .mailbox-mobile-rail-avatar {
    background: #d97706;
  }

  .mailbox-mobile-menu-panel {
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    padding: 22px 18px;
    background: #fff;
  }

  .mailbox-mobile-menu-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 38px;
    gap: 10px;
    align-items: start;
    padding-bottom: 16px;
    border-bottom: 1px solid #eef2f6;
  }

  .mailbox-mobile-menu-head span,
  .mailbox-mobile-nav-title {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 900;
    text-transform: uppercase;
  }

  .mailbox-mobile-menu-head strong {
    display: block;
    overflow: hidden;
    margin-top: 2px;
    color: #111827;
    font-size: 0.96rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mailbox-mobile-menu-close {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    color: #64748b;
    background: #f1f5f9;
  }

  .mailbox-mobile-menu-nav {
    display: grid;
    gap: 6px;
    padding-top: 14px;
  }

  .mailbox-mobile-nav-title {
    margin: 12px 0 4px;
  }

  .mailbox-mobile-menu-nav .mailbox-folder-btn {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr) auto;
    min-height: 46px;
    padding: 0 6px;
    border: 0;
    border-radius: 8px;
    color: #4b5563;
    background: #fff;
    font-size: 1rem;
  }

  .mailbox-mobile-menu-nav .mailbox-folder-btn i {
    color: #7b8794;
    font-size: 1rem;
  }

  .mailbox-mobile-menu-nav .mailbox-folder-btn.active,
  .mailbox-mobile-menu-nav .mailbox-folder-btn:hover {
    color: #0877b7;
    background: #eef8fd;
  }

  .mailbox-mobile-menu-nav .mailbox-folder-btn.active i,
  .mailbox-mobile-menu-nav .mailbox-folder-btn:hover i {
    color: #0877b7;
  }

  .mailbox-compose-backdrop {
    top: 0;
    z-index: 6600;
    padding: 0;
    background: rgba(15, 23, 42, 0.44);
  }

  .mailbox-compose-drawer {
    width: 100vw;
    max-height: 100dvh;
    min-height: 100dvh;
    border-radius: 0;
  }

  .mailbox-compose-head {
    align-items: center;
    flex-direction: row;
    min-height: 64px;
    padding: 12px 16px;
    background: #0877b7;
  }

  .mailbox-compose-head h2 {
    font-size: 1.12rem;
  }

  .mailbox-compose-form {
    gap: 12px;
    padding: 14px 16px 18px;
  }

  .mailbox-compose-recipients,
  .mailbox-smart-panel {
    border-radius: 8px;
    background: #f8fafc;
  }

  .mailbox-recipient-row {
    grid-template-columns: 38px minmax(0, 1fr);
  }

  .mailbox-recipient-box input {
    min-width: 120px;
  }

  .mailbox-contact-suggestions {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 420px) {
  .mailbox-mobile-menu-backdrop {
    --mailbox-mobile-nav-offset: 62px;
  }

  .mailbox-mobile-header {
    grid-template-columns: 40px minmax(0, 1fr) 38px 38px;
    gap: 8px;
    padding-inline: 14px;
  }

  .mailbox-mobile-heading h1 {
    font-size: 1.18rem;
  }

  .mailbox-mobile-filterbar {
    padding-inline: 14px;
  }

  .mailbox-mobile-pills button {
    min-width: 76px;
    padding-inline: 10px;
  }

  .mailbox-mobile-menu {
    grid-template-columns: 68px minmax(0, 1fr);
    width: 94vw;
  }

  .mailbox-mobile-rail-btn,
  .mailbox-mobile-rail-avatar {
    width: 46px;
    height: 46px;
  }

  .mailbox-message-item {
    padding-inline: 18px;
  }
}
</style>
