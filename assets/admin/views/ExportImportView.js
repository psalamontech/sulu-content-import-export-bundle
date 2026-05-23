import React from 'react';
import {action, computed, observable, reaction, runInAction, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {withToolbar} from 'sulu-admin-bundle/containers';
import {Dialog, Loader, Tabs} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {getJson, postProtectedJson} from '../services/exportImportRequester';

const styles = {
    page: {
        minHeight: 'calc(100vh - 190px)',
        paddingBottom: 32,
    },
    metaCard: {
        background: '#ffffff',
        border: '1px solid #dcdcdc',
        borderRadius: 6,
        boxShadow: '0 1px 0 rgba(0, 0, 0, 0.03)',
        marginBottom: 24,
        overflow: 'hidden',
    },
    metaInner: {
        alignItems: 'center',
        display: 'flex',
        flexWrap: 'wrap',
        gap: 10,
        justifyContent: 'space-between',
        padding: '18px 24px',
    },
    titleWrap: {
        alignItems: 'baseline',
        display: 'flex',
        flexWrap: 'wrap',
        gap: 8,
    },
    metaDetails: {
        color: '#7a7a7a',
        display: 'flex',
        flexWrap: 'wrap',
        fontSize: 12,
        gap: 14,
        marginTop: 6,
    },
    metaDetailLabel: {
        color: '#9a9a9a',
        fontWeight: 600,
        marginRight: 6,
        textTransform: 'uppercase',
    },
    metaDetailValue: {
        color: '#4f4f4f',
        fontFamily: '\'SF Mono\', \'Fira Code\', Consolas, monospace',
    },
    title: {
        color: '#1f1f1f',
        fontSize: 20,
        fontWeight: 600,
        lineHeight: 1.2,
    },
    subtitle: {
        color: '#6f6f6f',
        fontSize: 14,
        fontStyle: 'italic',
    },
    locale: {
        color: '#9a9a9a',
        fontSize: 13,
        fontWeight: 500,
    },
    badgeRow: {
        alignItems: 'center',
        display: 'flex',
        flexWrap: 'wrap',
        gap: 8,
        justifyContent: 'flex-end',
    },
    badge: {
        borderRadius: 999,
        display: 'inline-flex',
        fontSize: 12,
        fontWeight: 600,
        lineHeight: 1,
        padding: '6px 10px',
    },
    contentCard: {
        background: '#ffffff',
        border: '1px solid #dcdcdc',
        borderRadius: 6,
        boxShadow: '0 1px 0 rgba(0, 0, 0, 0.03)',
        marginBottom: 18,
        padding: 24,
    },
    sectionTitleRow: {
        alignItems: 'center',
        display: 'flex',
        gap: 16,
        justifyContent: 'space-between',
        marginBottom: 14,
    },
    sectionTitle: {
        color: '#6b6b6b',
        fontSize: 12,
        fontWeight: 700,
        letterSpacing: '.06em',
        textTransform: 'uppercase',
    },
    errorMessage: {
        background: '#fff0ee',
        border: '1px solid #ffd7d2',
        borderRadius: 4,
        color: '#d84a3a',
        fontSize: 12,
        maxWidth: '70%',
        padding: '7px 12px',
    },
    preview: {
        background: '#1e1e1e',
        border: '1px solid #161616',
        boxSizing: 'border-box',
        borderRadius: 6,
        boxShadow: 'inset 0 0 0 1px rgba(255,255,255,.02)',
        color: '#d4d4d4',
        cursor: 'text',
        display: 'block',
        fontFamily: '\'SF Mono\', \'Fira Code\', Consolas, monospace',
        fontSize: 13,
        height: 430,
        lineHeight: 1.65,
        margin: 0,
        minHeight: 430,
        overflowX: 'auto',
        overflowY: 'auto',
        padding: 22,
        whiteSpace: 'pre-wrap',
        wordBreak: 'break-all',
    },
    editor: {
        background: '#1e1e1e',
        border: '2px solid #1f5eff',
        boxSizing: 'border-box',
        borderRadius: 6,
        color: '#d4d4d4',
        display: 'block',
        fontFamily: '\'SF Mono\', \'Fira Code\', Consolas, monospace',
        fontSize: 13,
        height: 430,
        lineHeight: 1.65,
        minHeight: 430,
        outline: 'none',
        overflowY: 'auto',
        padding: 22,
        resize: 'vertical',
        whiteSpace: 'pre-wrap',
        width: '100%',
        wordBreak: 'break-all',
    },
    validationHeader: {
        alignItems: 'center',
        display: 'flex',
        flexWrap: 'wrap',
        gap: 14,
        justifyContent: 'space-between',
        marginBottom: 12,
    },
    legend: {
        color: '#727272',
        display: 'flex',
        flexWrap: 'wrap',
        fontSize: 12,
        gap: 16,
    },
    validationTable: {
        border: '1px solid #dcdcdc',
        borderCollapse: 'collapse',
        borderRadius: 6,
        tableLayout: 'fixed',
        overflow: 'hidden',
        width: '100%',
    },
    validationTh: {
        background: '#f6f6f6',
        borderBottom: '1px solid #dcdcdc',
        color: '#666',
        fontSize: 13,
        fontWeight: 600,
        padding: '10px 12px',
        textAlign: 'left',
    },
    validationTd: {
        borderTop: '1px solid #efefef',
        fontSize: 13,
        padding: '10px 12px',
        verticalAlign: 'middle',
    },
    validationCode: {
        background: '#f3f4f6',
        borderRadius: 3,
        color: '#3d4351',
        fontFamily: 'monospace',
        fontSize: 12,
        padding: '2px 5px',
    },
    infoNotice: {
        background: '#ebf7f0',
        borderRadius: 999,
        color: '#2d8a57',
        display: 'inline-flex',
        fontSize: 12,
        fontWeight: 600,
        padding: '6px 10px',
    },
    alert: {
        background: '#fff0ee',
        border: '1px solid #ffd7d2',
        borderRadius: 6,
        color: '#d84a3a',
        marginBottom: 18,
        padding: '12px 16px',
    },
    reloadButton: {
        background: '#ffffff',
        border: '1px solid #dcdcdc',
        borderRadius: 4,
        color: '#222',
        cursor: 'pointer',
        fontSize: 13,
        fontWeight: 600,
        marginTop: 10,
        padding: '8px 12px',
    },
};

function issueBadgeStyle(type) {
    switch (type) {
        case 'error':
            return {background: '#fff0ee', color: '#d84a3a'};
        case 'warning':
            return {background: '#fff7da', color: '#8f6400'};
        case 'info':
            return {background: '#eef3ff', color: '#2859b8'};
        case 'success':
            return {background: '#ebf7f0', color: '#2d8a57'};
        default:
            return {};
    }
}

function rowStyle(level) {
    switch (level) {
        case 'error':
            return {background: '#fff9f8'};
        case 'warning':
            return {background: '#fffdf2'};
        default:
            return {background: '#fcfcfc'};
    }
}

function levelIcon(level) {
    switch (level) {
        case 'error':
            return '✕';
        case 'warning':
            return '⚠';
        default:
            return '○';
    }
}

@observer
class ExportImportView extends React.Component {
    @observable activeTab = 'content';
    @observable copied = false;
    @observable contentEditorVisible = false;
    @observable documentId = '';
    @observable initialContentText = '';
    @observable initialSeoText = '{}';
    @observable contentSyntaxError = '';
    @observable contentText = '';
    @observable globalError = '';
    @observable issues = [];
    @observable loading = true;
    @observable pendingLocale = undefined;
    @observable saving = false;
    @observable seoEditorVisible = false;
    @observable seoSyntaxError = '';
    @observable seoText = '{}';
    @observable showLocaleWarning = false;
    @observable structureType = '';
    @observable payloadLocale = '';
    showSuccess = observable.box(false);
    data = undefined;
    revalidateTimer = undefined;
    copiedTimer = undefined;
    routeReactionDisposer = undefined;
    successTimer = undefined;

    componentDidMount() {
        this.routeReactionDisposer = reaction(
            () => {
                const activeRouteName = this.props.router.route.name;
                const currentViewName = this.props.route.name;
                const {id, locale, webspace} = this.props.router.attributes;

                return [activeRouteName, currentViewName, id, locale, webspace];
            },
            () => {
                this.loadIfActive();
            },
            {fireImmediately: true}
        );
    }

    componentWillUnmount() {
        if (this.routeReactionDisposer) {
            this.routeReactionDisposer();
        }

        window.clearTimeout(this.revalidateTimer);
        window.clearTimeout(this.copiedTimer);
        window.clearTimeout(this.successTimer);
    }

    @computed get contentName() {
        return this.props.route.options.contentName || 'Document';
    }

    @computed get contentTabLabel() {
        return this.props.route.options.contentTabLabel || this.contentName;
    }

    @computed get currentIssueCounts() {
        return this.issues.reduce((counts, issue) => {
            counts[issue.level] = (counts[issue.level] || 0) + 1;

            return counts;
        }, {error: 0, warning: 0, info: 0});
    }

    @computed get isActiveView() {
        return this.props.router.route.name === this.props.route.name;
    }

    @computed get endpointBase() {
        const {id, locale, webspace} = this.props.router.attributes;
        const params = new URLSearchParams({locale: locale || 'en'});
        if (webspace) {
            params.set('webspace', webspace);
        }

        return `/admin/${this.props.route.options.urlPrefix}/${id}?${params.toString()}`;
    }

    @computed get hasContentErrors() {
        return this.currentIssueCounts.error > 0;
    }

    @computed get hasSeo() {
        return !!this.props.route.options.hasSeo;
    }

    @computed get locale() {
        return this.props.router.attributes.locale || 'en';
    }

    @computed get saveLabel() {
        return this.props.route.options.saveLabel || 'Save';
    }

    @computed get seoEndpoint() {
        return `${this.endpointBase.replace(/\?.*$/, '')}/seo?${this.endpointBase.split('?')[1]}`;
    }

    @computed get selectedTabIndex() {
        return this.activeTab === 'seo' ? 1 : 0;
    }

    @computed get structureLabel() {
        return this.structureType || '...';
    }

    @computed get validateEndpoint() {
        return `${this.endpointBase.replace(/\?.*$/, '')}/validate?${this.endpointBase.split('?')[1]}`;
    }

    @computed get canSave() {
        if (this.loading || this.saving || this.globalError) {
            return false;
        }

        if (this.activeTab === 'seo') {
            return this.hasSeo && this.isSeoDirty && !this.seoSyntaxError;
        }

        return this.isContentDirty && !this.contentSyntaxError && !this.hasContentErrors;
    }

    @computed get isContentDirty() {
        return this.contentText !== this.initialContentText;
    }

    @computed get isSeoDirty() {
        return this.seoText !== this.initialSeoText;
    }

    @computed get isDirty() {
        return this.isContentDirty || this.isSeoDirty;
    }

    @computed get backButtonConfig() {
        const {
            router: {
                route: {
                    options: {
                        backView,
                    },
                },
            },
        } = this.props;

        if (!backView) {
            return undefined;
        }

        return {
            onClick: this.navigateBack,
        };
    }

    @computed get localeOptions() {
        const locales = this.props.route.options.locales || [];

        return locales.map((locale) => ({
            label: locale,
            value: locale,
        }));
    }

    @computed get localeToolbarConfig() {
        if (this.localeOptions.length <= 1) {
            return undefined;
        }

        return {
            onChange: this.handleLocaleChange,
            options: this.localeOptions,
            value: this.locale,
        };
    }

    @action.bound
    async load() {
        runInAction(() => {
            this.loading = true;
            this.globalError = '';
        });

        try {
            const payload = await getJson(this.endpointBase);

            runInAction(() => {
                const formattedContent = this.formatContentPayload(payload);
                const formattedSeo = JSON.stringify(payload.seo || {}, null, 4);

                this.documentId = payload.id || '';
                this.payloadLocale = payload.locale || this.locale;
                this.structureType = payload.structureType;
                this.issues = payload.issues || [];
                this.contentText = formattedContent;
                this.initialContentText = formattedContent;
                this.seoText = formattedSeo;
                this.initialSeoText = formattedSeo;
                this.contentSyntaxError = '';
                this.seoSyntaxError = '';
                this.contentEditorVisible = false;
                this.seoEditorVisible = false;
            });
        } catch (error) {
            runInAction(() => {
                this.globalError = error.message || 'Failed to load export/import data.';
            });
        } finally {
            runInAction(() => {
                this.loading = false;
            });
        }
    }

    @action.bound
    resetInactiveState() {
        this.loading = false;
        this.globalError = '';
    }

    @action.bound
    loadIfActive() {
        const {id} = this.props.router.attributes;

        if (!this.isActiveView || !id) {
            this.resetInactiveState();

            return;
        }

        this.load();
    }

    formatContentPayload(payload) {
        return JSON.stringify({
            content: payload.content,
        }, null, 4);
    }

    renderSyntaxHighlightedJson(json) {
        const tokenColors = {
            boolean: '#569cd6',
            key: '#9cdcfe',
            null: '#569cd6',
            number: '#b5cea8',
            string: '#ce9178',
        };
        const tokenPattern = /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g;
        const nodes = [];
        let match;
        let lastIndex = 0;

        while ((match = tokenPattern.exec(json)) !== null) {
            const [token] = match;
            if (match.index > lastIndex) {
                nodes.push(json.slice(lastIndex, match.index));
            }

            let cssClass = 'number';
            if (/^"/.test(token)) {
                cssClass = /:$/.test(token) ? 'key' : 'string';
            } else if (/true|false/.test(token)) {
                cssClass = 'boolean';
            } else if (/null/.test(token)) {
                cssClass = 'null';
            }

            nodes.push(
                <span key={`${match.index}-${token}`} style={{color: tokenColors[cssClass]}}>
                    {token}
                </span>
            );
            lastIndex = match.index + token.length;
        }

        if (lastIndex < json.length) {
            nodes.push(json.slice(lastIndex));
        }

        return nodes;
    }

    @action.bound
    navigateToLocale(locale) {
        if (locale === this.locale) {
            return;
        }

        this.props.router.navigate(this.props.router.route.name, {
            ...this.props.router.attributes,
            locale,
        });
    }

    @action.bound
    navigateBack() {
        const {
            router,
            router: {
                attributes,
                route: {
                    options: {
                        backView,
                        routerAttributesToBackView,
                    },
                },
            },
        } = this.props;

        if (!backView) {
            return;
        }

        const backViewParameters = {};

        if (routerAttributesToBackView) {
            Object.keys(toJS(routerAttributesToBackView)).forEach((key) => {
                const backViewOptionKey = routerAttributesToBackView[key];
                const attributeName = isNaN(key) ? key : routerAttributesToBackView[key];

                backViewParameters[backViewOptionKey] = attributes[attributeName];
            });
        }

        if (attributes.locale) {
            backViewParameters.locale = attributes.locale;
        }

        router.restore(backView, backViewParameters);
    }

    @action.bound
    handleLocaleChange(locale) {
        if (locale === this.locale) {
            return;
        }

        if (this.isDirty) {
            this.pendingLocale = locale;
            this.showLocaleWarning = true;

            return;
        }

        this.navigateToLocale(locale);
    }

    @action.bound
    handleLocaleWarningCancel() {
        this.pendingLocale = undefined;
        this.showLocaleWarning = false;
    }

    @action.bound
    handleLocaleWarningConfirm() {
        if (!this.pendingLocale) {
            throw new Error('The target locale is missing. This should not happen and is likely a bug.');
        }

        const locale = this.pendingLocale;
        this.pendingLocale = undefined;
        this.showLocaleWarning = false;
        this.navigateToLocale(locale);
    }

    @action.bound
    setActiveTab(index) {
        if (!this.hasSeo) {
            this.activeTab = 'content';

            return;
        }

        this.activeTab = index === 1 ? 'seo' : 'content';
    }

    @action.bound
    showContentEditor() {
        this.contentEditorVisible = true;
    }

    @action.bound
    showSeoEditor() {
        this.seoEditorVisible = true;
    }

    @action.bound
    hideContentEditor() {
        this.contentEditorVisible = false;
    }

    @action.bound
    hideSeoEditor() {
        this.seoEditorVisible = false;
    }

    @action.bound
    handleContentChange(event) {
        this.contentText = event.currentTarget.value;
        this.validateContentSyntax();

        if (!this.contentSyntaxError) {
            window.clearTimeout(this.revalidateTimer);
            this.revalidateTimer = window.setTimeout(this.revalidate.bind(this), 400);
        }
    }

    @action.bound
    handleSeoChange(event) {
        this.seoText = event.currentTarget.value;
        this.validateSeoSyntax();
    }

    @action.bound
    handleEditorBlur() {
        window.clearTimeout(this.revalidateTimer);
        if (!this.validateContentSyntax()) {
            return;
        }

        this.revalidate();
        this.hideContentEditor();
    }

    @action.bound
    handleSeoBlur() {
        this.validateSeoSyntax();
        this.hideSeoEditor();
    }

    @action.bound
    handleEditorKeyDown(event) {
        if (event.key !== 'Tab') {
            return;
        }

        event.preventDefault();
        const {selectionStart, selectionEnd, value} = event.currentTarget;
        const nextValue = value.substring(0, selectionStart) + '    ' + value.substring(selectionEnd);
        this.contentText = nextValue;

        requestAnimationFrame(() => {
            event.currentTarget.selectionStart = selectionStart + 4;
            event.currentTarget.selectionEnd = selectionStart + 4;
        });
    }

    @action.bound
    handleSeoEditorKeyDown(event) {
        if (event.key !== 'Tab') {
            return;
        }

        event.preventDefault();
        const {selectionStart, selectionEnd, value} = event.currentTarget;
        const nextValue = value.substring(0, selectionStart) + '    ' + value.substring(selectionEnd);
        this.seoText = nextValue;

        requestAnimationFrame(() => {
            event.currentTarget.selectionStart = selectionStart + 4;
            event.currentTarget.selectionEnd = selectionStart + 4;
        });
    }

    @action.bound
    validateContentSyntax() {
        try {
            JSON.parse(this.contentText);
            this.contentSyntaxError = '';

            return true;
        } catch (error) {
            this.contentSyntaxError = `JSON syntax error: ${error.message}`;

            return false;
        }
    }

    @action.bound
    validateSeoSyntax() {
        if (!this.hasSeo) {
            this.seoSyntaxError = '';

            return true;
        }

        try {
            JSON.parse(this.seoText);
            this.seoSyntaxError = '';

            return true;
        } catch (error) {
            this.seoSyntaxError = `JSON syntax error: ${error.message}`;

            return false;
        }
    }

    @action.bound
    async revalidate() {
        if (!this.validateContentSyntax()) {
            return;
        }

        try {
            const parsed = JSON.parse(this.contentText);
            const payload = await postProtectedJson(this.validateEndpoint, {
                structureType: this.structureType,
                content: parsed.content,
            });
            runInAction(() => {
                this.issues = payload.issues || [];
                this.globalError = '';
            });
        } catch (error) {
            runInAction(() => {
                this.globalError = error.message || 'Validation failed.';
            });
        }
    }

    @action.bound
    async handleSave() {
        if (this.activeTab === 'seo') {
            await this.saveSeo();

            return;
        }

        await this.saveContent();
    }

    @action.bound
    async saveContent() {
        if (!this.validateContentSyntax()) {
            return;
        }

        runInAction(() => {
            this.saving = true;
            this.globalError = '';
        });

        try {
            const parsed = JSON.parse(this.contentText);
            await postProtectedJson(this.endpointBase, parsed);
            runInAction(() => {
                this.initialContentText = this.contentText;
            });
            this.showSuccessState();
            await this.revalidate();
        } catch (error) {
            runInAction(() => {
                this.globalError = error.message || 'Save failed.';
            });
        } finally {
            runInAction(() => {
                this.saving = false;
            });
        }
    }

    @action.bound
    async saveSeo() {
        if (!this.validateSeoSyntax()) {
            return;
        }

        runInAction(() => {
            this.saving = true;
            this.globalError = '';
        });

        try {
            const parsed = JSON.parse(this.seoText);
            await postProtectedJson(this.seoEndpoint, {seo: parsed});
            runInAction(() => {
                this.initialSeoText = this.seoText;
            });
            this.showSuccessState();
        } catch (error) {
            runInAction(() => {
                this.globalError = error.message || 'SEO save failed.';
            });
        } finally {
            runInAction(() => {
                this.saving = false;
            });
        }
    }

    @action.bound
    showSuccessState() {
        this.showSuccess.set(true);
        window.clearTimeout(this.successTimer);
        this.successTimer = window.setTimeout(action(() => {
            this.showSuccess.set(false);
        }), 1800);
    }

    @action.bound
    async handleCopy() {
        const source = this.activeTab === 'seo' ? this.seoText : this.contentText;
        await navigator.clipboard.writeText(source);

        runInAction(() => {
            this.copied = true;
        });
        window.clearTimeout(this.copiedTimer);
        this.copiedTimer = window.setTimeout(action(() => {
            this.copied = false;
        }), 1600);
    }

    renderBadges() {
        const badges = [];
        const counts = this.currentIssueCounts;

        if (this.activeTab === 'seo' && this.hasSeo) {
            if (this.seoSyntaxError) {
                badges.push({label: 'JSON syntax error', type: 'error'});
            } else {
                badges.push({label: 'SEO JSON OK', type: 'success'});
            }
        } else if (this.contentSyntaxError) {
            badges.push({label: 'JSON syntax error', type: 'error'});
        } else {
            if (counts.error > 0) {
                badges.push({
                    label: counts.error === 1 ? '1 required field missing' : `${counts.error} required fields missing`,
                    type: 'error',
                });
            }
            if (counts.warning > 0) {
                badges.push({label: `${counts.warning} warning${counts.warning > 1 ? 's' : ''}`, type: 'warning'});
            }
            if (counts.info > 0) {
                badges.push({label: `${counts.info} empty optional field${counts.info > 1 ? 's' : ''}`, type: 'info'});
            }
            if (counts.error === 0 && counts.warning === 0) {
                badges.push({label: 'All required fields OK', type: 'success'});
            }
        }

        return (
            <div style={styles.badgeRow}>
                {badges.map((badge) => (
                    <span key={badge.label} style={{...styles.badge, ...issueBadgeStyle(badge.type)}}>
                        {badge.label}
                    </span>
                ))}
            </div>
        );
    }

    renderValidation() {
        if (!this.issues.length) {
            return <span style={styles.infoNotice}>✓ All required fields OK</span>;
        }

        return (
            <div style={styles.contentCard}>
                <div style={styles.validationHeader}>
                    <div style={styles.sectionTitle}>Validation</div>
                    <div style={styles.legend}>
                        <span><span style={{color: '#d84a3a', fontWeight: 700}}>✕</span> Required field empty</span>
                        <span><span style={{color: '#8f6400', fontWeight: 700}}>⚠</span> Warning</span>
                        <span><span style={{color: '#8b8b8b', fontWeight: 700}}>○</span> Optional field empty</span>
                    </div>
                </div>
                <table style={styles.validationTable}>
                    <colgroup>
                        <col style={{width: '32px'}} />
                        <col style={{width: '44%'}} />
                        <col />
                    </colgroup>
                    <thead>
                        <tr>
                            <th style={styles.validationTh} />
                            <th style={styles.validationTh}>Field path</th>
                            <th style={styles.validationTh}>Issue</th>
                        </tr>
                    </thead>
                    <tbody>
                        {this.issues.map((issue) => (
                            <tr key={`${issue.level}-${issue.path}-${issue.message}`} style={rowStyle(issue.level)}>
                                <td style={{...styles.validationTd, color: issue.level === 'error' ? '#d84a3a' : issue.level === 'warning' ? '#8f6400' : '#8b8b8b', fontWeight: 700, textAlign: 'center'}}>
                                    {levelIcon(issue.level)}
                                </td>
                                <td style={styles.validationTd}>
                                    <code style={styles.validationCode}>{issue.path}</code>
                                </td>
                                <td style={styles.validationTd}>{issue.message}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    }

    renderEditor(title, value, previewVisible, syntaxError, onPreviewClick, onChange, onBlur, onKeyDown) {
        return (
            <div style={styles.contentCard}>
                <div style={styles.sectionTitleRow}>
                    <div style={styles.sectionTitle}>{title}</div>
                    {syntaxError ? <div style={styles.errorMessage}>{syntaxError}</div> : null}
                </div>
                {previewVisible ? (
                    <pre
                        onClick={onPreviewClick}
                        style={styles.preview}
                    >
                        {this.renderSyntaxHighlightedJson(value)}
                    </pre>
                ) : (
                    <textarea
                        autoComplete="off"
                        onBlur={onBlur}
                        onChange={onChange}
                        onKeyDown={onKeyDown}
                        spellCheck="false"
                        style={{
                            ...styles.editor,
                            borderColor: syntaxError ? '#d84a3a' : '#1f5eff',
                        }}
                        value={value}
                    />
                )}
            </div>
        );
    }

    render() {
        if (this.loading) {
            return (
                <div style={{...styles.page, alignItems: 'center', display: 'flex', justifyContent: 'center'}}>
                    <Loader size={48} />
                </div>
            );
        }

        if (this.globalError && !this.contentText) {
            return (
                <div style={styles.page}>
                    <div style={styles.alert}>
                        {this.globalError}
                        <div>
                            <button onClick={this.load} style={styles.reloadButton} type="button">
                                Reload
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return (
            <div style={styles.page}>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={this.handleLocaleWarningCancel}
                    onConfirm={this.handleLocaleWarningConfirm}
                    open={this.showLocaleWarning}
                    title={translate('sulu_admin.dirty_warning_dialog_title')}
                >
                    {translate('sulu_admin.dirty_warning_dialog_text')}
                </Dialog>
                {this.globalError ? <div style={styles.alert}>{this.globalError}</div> : null}
                <div style={styles.metaCard}>
                    <div style={styles.metaInner}>
                        <div>
                            <div style={styles.titleWrap}>
                                <span style={styles.title}>{translate('sulu_content_import_export.export_import')}</span>
                                <span style={styles.subtitle}>{this.structureLabel}</span>
                                <span style={styles.locale}>[{this.payloadLocale || this.locale}]</span>
                            </div>
                            <div style={styles.metaDetails}>
                                <span>
                                    <span style={styles.metaDetailLabel}>ID</span>
                                    <span style={styles.metaDetailValue}>{this.documentId || '...'}</span>
                                </span>
                            </div>
                        </div>
                        {this.renderBadges()}
                    </div>
                    {this.hasSeo ? (
                        <div style={{borderTop: '1px solid #dcdcdc', padding: '0 24px'}}>
                            <Tabs onSelect={this.setActiveTab} selectedIndex={this.selectedTabIndex} type="inline">
                                <Tabs.Tab>{this.contentTabLabel}</Tabs.Tab>
                                <Tabs.Tab>SEO</Tabs.Tab>
                            </Tabs>
                        </div>
                    ) : null}
                </div>

                {this.activeTab === 'content'
                    ? (
                        <div>
                            {this.renderEditor(
                                `${this.contentName.toUpperCase()} JSON`,
                                this.contentText,
                                !this.contentEditorVisible,
                                this.contentSyntaxError,
                                this.showContentEditor,
                                this.handleContentChange,
                                this.handleEditorBlur,
                                this.handleEditorKeyDown
                            )}
                            {this.renderValidation()}
                        </div>
                    )
                    : this.renderEditor(
                        'SEO JSON',
                        this.seoText,
                        !this.seoEditorVisible,
                        this.seoSyntaxError,
                        this.showSeoEditor,
                        this.handleSeoChange,
                        this.handleSeoBlur,
                        this.handleSeoEditorKeyDown
                    )}
            </div>
        );
    }
}

export default withToolbar(ExportImportView, function() {
    return {
        backButton: this.backButtonConfig,
        items: [
            {
                disabled: this.loading || !this.contentText,
                label: this.copied ? 'Copied' : 'Copy JSON',
                onClick: this.handleCopy,
                type: 'button',
            },
            {
                disabled: !this.canSave,
                icon: 'su-save',
                label: this.saveLabel,
                loading: this.saving,
                onClick: this.handleSave,
                primary: this.canSave,
                type: 'button',
            },
        ],
        locale: this.localeToolbarConfig,
        showSuccess: this.showSuccess,
    };
});
